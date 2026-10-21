using System.Text.Json;
using CSharpSchool.Runner.Protocol;
using Microsoft.CodeAnalysis;
using Microsoft.CodeAnalysis.CSharp;
using Microsoft.CodeAnalysis.CSharp.Syntax;

namespace CSharpSchool.Runner.Checks;

/// <summary>
/// Другие начальные значения для проверки вывода (<c>set</c>): в синтаксическом дереве кода ученика
/// значение при объявлении переменной заменяется литералом — <c>int height = 4;</c> → <c>int height = 5;</c>.
/// Меняется только копия кода для этой проверки.
/// <list type="bullet">
/// <item>Переменная — из основного кода (top-level statements): не внутри цикла, блока или метода,
/// объявлена ровно один раз и сразу со значением. Так ученик не подменит её случайно в другом месте.</item>
/// <item>Значение — литерал простого типа: int, long ← целое; double, float, decimal ← любое число;
/// string ← строка; char ← строка из одного символа; bool ← true/false. Для зерна Random задача
/// заводит отдельную переменную: <c>int seed = 42; Random random = new Random(seed);</c>.</item>
/// <item>Или JSON-массив для массива (<c>int[]</c>) и списка (<c>List&lt;int&gt;</c>) простых значений:
/// <c>[5, 9, 1]</c> → <c>new int[] { 5, 9, 1 }</c> — так же для <c>{ … }</c>, <c>new[] { … }</c> и
/// <c>new int[3] { … }</c> (длина берётся из нового списка). Каждый элемент — по правилам выше.</item>
/// </list>
/// Число строк кода не меняется: исключение показывается на той же строке, что у ученика.
/// </summary>
internal static class InitialValues
{
    /// <summary>Типы, значения которых подставляются литералом (см. Literal).</summary>
    private static readonly HashSet<SpecialType> Simple =
    [
        SpecialType.System_Int32,
        SpecialType.System_Int64,
        SpecialType.System_Double,
        SpecialType.System_Single,
        SpecialType.System_Decimal,
        SpecialType.System_String,
        SpecialType.System_Char,
        SpecialType.System_Boolean,
    ];

    /// <returns>Код с подменёнными значениями или причина, почему подменить нельзя.</returns>
    public static (string? Code, SetError? Error) Apply(SyntaxTree tree, SemanticModel model, IReadOnlyDictionary<string, JsonElement> values)
    {
        var root = (CompilationUnitSyntax)tree.GetRoot();
        var replacements = new Dictionary<ExpressionSyntax, ExpressionSyntax>();

        foreach (var (name, value) in values)
        {
            var declarators = root.Members
                .OfType<GlobalStatementSyntax>()
                .Select(global => global.Statement)
                .OfType<LocalDeclarationStatementSyntax>()
                .SelectMany(statement => statement.Declaration.Variables)
                .Where(declarator => declarator.Identifier.ValueText == name)
                .ToList();

            if (declarators.Count != 1)
            {
                return (null, new SetError(name, SetProblem.NotFound));
            }

            var declarator = declarators[0];

            if (declarator.Initializer is null)
            {
                return (null, new SetError(name, SetProblem.NoValue));
            }

            var type = (model.GetDeclaredSymbol(declarator) as ILocalSymbol)?.Type;
            var replacement = type is null ? null : Value(type, value, model);

            if (replacement is null)
            {
                return (null, new SetError(name, SetProblem.WrongType, type?.ToDisplayString(SymbolDisplayFormat.MinimallyQualifiedFormat)));
            }

            var old = declarator.Initializer.Value;
            replacements[old] = SameLines(replacement, old);
        }

        var changed = root.ReplaceNodes(replacements.Keys, (original, _) => replacements[original]);

        return (changed.ToFullString(), null);
    }

    /// <summary>Новое значение переменной этого типа или null, если значение к типу не подходит.</summary>
    private static ExpressionSyntax? Value(ITypeSymbol type, JsonElement value, SemanticModel model)
    {
        var element = type switch
        {
            IArrayTypeSymbol { Rank: 1 } array => array.ElementType,
            INamedTypeSymbol { TypeArguments: [var argument] } named
                when SymbolEqualityComparer.Default.Equals(named.OriginalDefinition, model.Compilation.GetTypeByMetadataName("System.Collections.Generic.List`1")) => argument,
            _ => null,
        };

        if (element is null)
        {
            return Literal(type.SpecialType, value);
        }

        // Тип элемента проверяется до перебора: пустой [] иначе подошёл бы и к Hero[], и к int[][]
        if (value.ValueKind != JsonValueKind.Array || !Simple.Contains(element.SpecialType))
        {
            return null;
        }

        var items = value.EnumerateArray().Select(item => Literal(element.SpecialType, item)).ToList();

        if (items.Contains(null))
        {
            return null;
        }

        // Полное имя типа (global::System.Collections.Generic.List<int>) собирается и без using.
        // Текст элементов — от Roslyn (кавычки и спецсимволы экранированы), поэтому разбор надёжен.
        // Массив — всегда new int[] { … }: подходит и вместо { … }, и вместо new int[3] { … }
        var typeName = type.ToDisplayString(SymbolDisplayFormat.FullyQualifiedFormat);
        var elements = string.Join(", ", items.Select(item => item!.ToFullString()));

        return SyntaxFactory.ParseExpression($"new {typeName} {{ {elements} }}");
    }

    /// <summary>
    /// Новое значение — на тех же строках, что старое: многострочный { … } превращается в одну
    /// строку и пустые строки после неё, чтобы номер строки исключения ниже не сдвинулся.
    /// </summary>
    private static ExpressionSyntax SameLines(ExpressionSyntax replacement, ExpressionSyntax old)
    {
        var lines = old.GetLocation().GetLineSpan();
        var removed = lines.EndLinePosition.Line - lines.StartLinePosition.Line;

        return replacement
            .WithLeadingTrivia(old.GetLeadingTrivia())
            .WithTrailingTrivia(Enumerable.Repeat(SyntaxFactory.EndOfLine("\n"), removed).Concat(old.GetTrailingTrivia()));
    }

    /// <summary>Литерал нужного типа или null, если значение к типу не подходит.</summary>
    private static LiteralExpressionSyntax? Literal(SpecialType type, JsonElement value) => (type, value.ValueKind) switch
    {
        (SpecialType.System_Int32, JsonValueKind.Number) when IsInteger(value) && value.TryGetInt32(out var number) => Number(SyntaxFactory.Literal(number)),
        (SpecialType.System_Int64, JsonValueKind.Number) when IsInteger(value) && value.TryGetInt64(out var number) => Number(SyntaxFactory.Literal(number)),
        (SpecialType.System_Double, JsonValueKind.Number) when value.TryGetDouble(out var number) && double.IsFinite(number) => Double(number),
        (SpecialType.System_Single, JsonValueKind.Number) when value.TryGetSingle(out var number) && float.IsFinite(number) => Number(SyntaxFactory.Literal(number)),
        (SpecialType.System_Decimal, JsonValueKind.Number) when value.TryGetDecimal(out var number) => Number(SyntaxFactory.Literal(number)),
        (SpecialType.System_String, JsonValueKind.String) when Text(value) is { } text => SyntaxFactory.LiteralExpression(SyntaxKind.StringLiteralExpression, SyntaxFactory.Literal(text)),
        (SpecialType.System_Char, JsonValueKind.String) when Text(value) is { Length: 1 } text => SyntaxFactory.LiteralExpression(SyntaxKind.CharacterLiteralExpression, SyntaxFactory.Literal(text[0])),
        (SpecialType.System_Boolean, JsonValueKind.True) => SyntaxFactory.LiteralExpression(SyntaxKind.TrueLiteralExpression),
        (SpecialType.System_Boolean, JsonValueKind.False) => SyntaxFactory.LiteralExpression(SyntaxKind.FalseLiteralExpression),
        _ => null,
    };

    /// <summary>Строка из JSON или null, если это не текст: одиночная половинка суррогатной пары («\ud800»).</summary>
    private static string? Text(JsonElement value)
    {
        try
        {
            return value.GetString();
        }
        catch (InvalidOperationException)
        {
            return null;
        }
    }

    /// <summary>
    /// Дробное число всегда с точкой или экспонентой: Roslyn печатает 3.0 как «3», а это уже int —
    /// <c>var speed = 3;</c> делит нацело, и <c>speed = speed * 1.5;</c> не компилируется.
    /// </summary>
    private static LiteralExpressionSyntax Double(double number)
    {
        var token = SyntaxFactory.Literal(number);

        return token.Text.AsSpan().IndexOfAny('.', 'e', 'E') < 0
            ? Number(SyntaxFactory.Literal(token.Text + ".0", number))
            : Number(token);
    }

    /// <summary>Целое в JSON — без дробной части и экспоненты: 5, а не 5.0 и не 5e0.</summary>
    private static bool IsInteger(JsonElement value) => value.GetRawText().AsSpan().IndexOfAny('.', 'e', 'E') < 0;

    /// <summary>
    /// Текст литерала Roslyn пишет сам: суффиксы f и m у float и decimal, «-5» у отрицательных
    /// (после печати дерева это снова читается как минус перед числом).
    /// </summary>
    private static LiteralExpressionSyntax Number(SyntaxToken token) =>
        SyntaxFactory.LiteralExpression(SyntaxKind.NumericLiteralExpression, token);
}
