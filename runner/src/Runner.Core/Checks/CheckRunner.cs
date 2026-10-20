using System.Globalization;
using CSharpSchool.Runner.Compilation;
using CSharpSchool.Runner.Execution;
using CSharpSchool.Runner.Protocol;
using Microsoft.CodeAnalysis;
using Microsoft.CodeAnalysis.CSharp.Syntax;

namespace CSharpSchool.Runner.Checks;

/// <summary>Проверки решения задачи: вывод программы и что есть в коде (переменные, конструкции, вызовы).</summary>
internal sealed class CheckRunner(ProgramRunner runner)
{
    private static readonly SymbolDisplayFormat FullName = SymbolDisplayFormat.FullyQualifiedFormat
        .WithGlobalNamespaceStyle(SymbolDisplayGlobalNamespaceStyle.Omitted)
        .WithGenericsOptions(SymbolDisplayGenericsOptions.None);

    public List<CheckResult> Run(StudentProgram program, IReadOnlyList<Check> checks, Action? onStart)
    {
        var model = program.Compilation.GetSemanticModel(program.Tree);
        var root = program.Tree.GetRoot();

        return [.. checks.Select((check, index) => check switch
        {
            OutputCheck output => Output(index, output, program, onStart),
            VariableCheck variable => Variable(index, variable, root, model),
            ConstructCheck construct => Construct(index, construct, root),
            CallCheck call => Call(index, call, root, model),
            _ => throw new NotSupportedException($"Неизвестная проверка {check.GetType().Name}"),
        })];
    }

    private CheckResult Output(int index, OutputCheck check, StudentProgram program, Action? onStart)
    {
        var result = runner.Run(program, check.Stdin, onStart);
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
            _ => false,
        });

        return new CheckResult(index, count >= check.Min, Text(check.Min), Text(count));
    }

    private static CheckResult Call(int index, CallCheck check, SyntaxNode root, SemanticModel model)
    {
        var count = root.DescendantNodes()
            .OfType<InvocationExpressionSyntax>()
            .Select(invocation => model.GetSymbolInfo(invocation).Symbol as IMethodSymbol)
            .Count(method => method is not null && $"{method.ContainingType.ToDisplayString(FullName)}.{method.Name}" == check.Method);

        return new CheckResult(index, count > 0, check.Method, Text(count));
    }

    private static string Text(int value) => value.ToString(CultureInfo.InvariantCulture);
}
