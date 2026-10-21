using System.Globalization;
using CSharpSchool.Runner.Compilation;
using CSharpSchool.Runner.Execution;
using CSharpSchool.Runner.Protocol;
using Microsoft.CodeAnalysis;
using Microsoft.CodeAnalysis.CSharp;
using Microsoft.CodeAnalysis.CSharp.Syntax;

namespace CSharpSchool.Runner.Checks;

/// <summary>Проверки решения задачи: вывод программы и что есть в коде (переменные, конструкции, вызовы).</summary>
internal sealed class CheckRunner(StudentCompiler compiler, ProgramRunner runner)
{
    private static readonly SymbolDisplayFormat FullName = SymbolDisplayFormat.FullyQualifiedFormat
        .WithGlobalNamespaceStyle(SymbolDisplayGlobalNamespaceStyle.Omitted)
        .WithGenericsOptions(SymbolDisplayGenericsOptions.None);

    public List<CheckResult> Run(StudentProgram program, IReadOnlyList<Check> checks, Action? onStart, Action? onEnd = null)
    {
        var model = program.Compilation.GetSemanticModel(program.Tree);
        var root = program.Tree.GetRoot();

        return [.. checks.Select((check, index) => check switch
        {
            OutputCheck output => Output(index, output, program, model, onStart, onEnd),
            VariableCheck variable => Variable(index, variable, root, model),
            ConstructCheck construct => Construct(index, construct, root),
            CallCheck call => Call(index, call, root, model),
            AccessCheck access => Access(index, access, root, model),
            _ => throw new NotSupportedException($"Неизвестная проверка {check.GetType().Name}"),
        })];
    }

    private CheckResult Output(int index, OutputCheck check, StudentProgram program, SemanticModel model, Action? onStart, Action? onEnd)
    {
        if (check.Set is { Count: > 0 })
        {
            var (code, error) = InitialValues.Apply(program.Tree, model, check.Set);
            var changed = code is null ? null : compiler.Compile(code);

            if (error is not null)
            {
                return new CheckResult(index, false, check.Expected, SetError: error);
            }

            if (changed is null || !changed.Compiled)
            {
                // Литерал подходит по типу, но код с ним может не собраться: const int size = 3;
                // и int[] items = new int[size] { 1, 2, 3 } — длина списка уже не совпадает
                return new CheckResult(index, false, check.Expected, SetError: new SetError(string.Join(", ", check.Set.Keys), SetProblem.NotCompiled));
            }

            program = changed;
        }

        var result = runner.Run(program, check.Stdin, onStart, onEnd);
        var actual = Normalize(result.Output);
        var expected = Normalize(check.Expected);
        var matches = check.Match == OutputMatch.Exact ? actual == expected : actual.Contains(expected, StringComparison.Ordinal);

        return new CheckResult(
            index,
            matches && result.Error is null && !result.OutputLimitExceeded,
            check.Expected,
            result.Output,
            result.OutputLimitExceeded,
            result.Error);
    }

    /// <summary>\r\n = \n; пробелы в конце строк и пустые строки в конце вывода не считаются.</summary>
    private static string Normalize(string text) =>
        string.Join('\n', text.ReplaceLineEndings("\n").Split('\n').Select(line => line.TrimEnd())).TrimEnd('\n');

    private static CheckResult Variable(int index, VariableCheck check, SyntaxNode root, SemanticModel model)
    {
        var declared = root.DescendantNodes()
            .Select(node => node switch
            {
                VariableDeclaratorSyntax declarator when declarator.Identifier.ValueText == check.Name => model.GetDeclaredSymbol(declarator),
                ForEachStatementSyntax loop when loop.Identifier.ValueText == check.Name => model.GetDeclaredSymbol(loop),
                _ => null,
            })
            .OfType<ISymbol>()
            .Select(symbol => symbol switch
            {
                ILocalSymbol local => local.Type,
                IFieldSymbol field => field.Type,
                _ => null,
            })
            .OfType<ITypeSymbol>()
            .Select(type => type.ToDisplayString(SymbolDisplayFormat.MinimallyQualifiedFormat))
            .ToList();

        var passed = declared.Count > 0 && (check.Type is null || declared.Contains(check.Type, StringComparer.Ordinal));

        return new CheckResult(index, passed, check.Type, declared.Count == 0 ? null : string.Join(", ", declared.Distinct()));
    }

    private static CheckResult Construct(int index, ConstructCheck check, SyntaxNode root)
    {
        var count = root.DescendantNodes().Count(node => check.Construct switch
        {
            Protocol.Construct.If => node is IfStatementSyntax,
            Protocol.Construct.Else => node is ElseClauseSyntax,
            Protocol.Construct.For => node is ForStatementSyntax,
            Protocol.Construct.Foreach => node is ForEachStatementSyntax,
            Protocol.Construct.While => node is WhileStatementSyntax,
            Protocol.Construct.DoWhile => node is DoStatementSyntax,
            Protocol.Construct.Switch => node is SwitchStatementSyntax or SwitchExpressionSyntax,
            Protocol.Construct.Method => node is MethodDeclarationSyntax or LocalFunctionStatementSyntax,
            Protocol.Construct.Class => node is ClassDeclarationSyntax,
            Protocol.Construct.Constructor => node is ConstructorDeclarationSyntax,
            Protocol.Construct.Property => node is PropertyDeclarationSyntax,
            Protocol.Construct.PrivateSet => node is AccessorDeclarationSyntax accessor
                && accessor.IsKind(SyntaxKind.SetAccessorDeclaration) && accessor.Modifiers.Any(SyntaxKind.PrivateKeyword),
            Protocol.Construct.This => node is ThisExpressionSyntax,
            Protocol.Construct.Break => node is BreakStatementSyntax && ExitsLoop(node),
            Protocol.Construct.Continue => node is ContinueStatementSyntax,
            _ => false,
        });

        return new CheckResult(index, count >= check.Min, Text(check.Min), Text(count));
    }

    /// <summary>break выходит из ближайшего цикла или switch — считается только выход из цикла.</summary>
    private static bool ExitsLoop(SyntaxNode node) =>
        node.Ancestors().FirstOrDefault(ancestor => ancestor
            is ForStatementSyntax or CommonForEachStatementSyntax or WhileStatementSyntax or DoStatementSyntax
            or SwitchSectionSyntax) is not (null or SwitchSectionSyntax);

    private static CheckResult Call(int index, CallCheck check, SyntaxNode root, SemanticModel model)
    {
        var count = root.DescendantNodes()
            .OfType<InvocationExpressionSyntax>()
            .Where(invocation => Name(model.GetSymbolInfo(invocation).Symbol as IMethodSymbol) == check.Method)
            .Count(invocation => check.Caller is null || InsideCaller(invocation, check.Caller, model));

        return new CheckResult(index, count > 0, check.Method, Text(count));
    }

    /// <summary>
    /// Вызов — в теле метода, конструктора или свойства с этим именем, на любой глубине: во вложенном методе
    /// или лямбде внутри него тоже. Сравнивается полное имя, поэтому перегрузки (методы с одним именем) не различаются.
    /// </summary>
    private static bool InsideCaller(SyntaxNode invocation, string caller, SemanticModel model) =>
        invocation.Ancestors()
            .Where(node => node is MethodDeclarationSyntax or LocalFunctionStatementSyntax or ConstructorDeclarationSyntax or PropertyDeclarationSyntax)
            .Any(member => Name(model.GetDeclaredSymbol(member)) == caller);

    /// <summary>
    /// Полное имя, как в проверке: «System.Console.WriteLine», свои методы — «Program.Report», метод класса —
    /// «Hero.Heal», конструктор — «Hero.Hero» (а не внутреннее «.ctor»), поле и свойство — «Hero.hp».
    /// </summary>
    private static string? Name(ISymbol? symbol) => symbol switch
    {
        IMethodSymbol { MethodKind: MethodKind.Constructor or MethodKind.StaticConstructor } constructor =>
            $"{constructor.ContainingType.ToDisplayString(FullName)}.{constructor.ContainingType.Name}",
        IMethodSymbol or IPropertySymbol or IFieldSymbol => $"{symbol.ContainingType.ToDisplayString(FullName)}.{symbol.Name}",
        _ => null,
    };

    private static CheckResult Access(int index, AccessCheck check, SyntaxNode root, SemanticModel model)
    {
        var count = root.DescendantNodes()
            .OfType<SimpleNameSyntax>()
            .Select(name => (Name: name, Symbol: model.GetSymbolInfo(name).Symbol))
            .Count(found => found.Symbol is IFieldSymbol or IPropertySymbol && Name(found.Symbol) == check.Member && !IsWritten(found.Name));

        return new CheckResult(index, count >= check.Min, Text(check.Min), Text(count));
    }

    /// <summary>Имя — то, во что записывают: слева от =, +=, у ++ и --, после out (hero.hp и this.hp — целиком).</summary>
    private static bool IsWritten(SimpleNameSyntax name)
    {
        ExpressionSyntax target = name.Parent is MemberAccessExpressionSyntax access && access.Name == name ? access : name;

        return target.Parent switch
        {
            AssignmentExpressionSyntax assignment => assignment.Left == target,
            PostfixUnaryExpressionSyntax or PrefixUnaryExpressionSyntax => target.Parent.IsKind(SyntaxKind.PostIncrementExpression)
                || target.Parent.IsKind(SyntaxKind.PostDecrementExpression)
                || target.Parent.IsKind(SyntaxKind.PreIncrementExpression)
                || target.Parent.IsKind(SyntaxKind.PreDecrementExpression),
            ArgumentSyntax argument => argument.RefKindKeyword.IsKind(SyntaxKind.OutKeyword),
            _ => false,
        };
    }

    private static string Text(int value) => value.ToString(CultureInfo.InvariantCulture);
}
