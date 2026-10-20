using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner.Tests;

/// <summary>
/// Частые ошибки новичка: код ошибки компилятора и её аргументы. Фронт переводит их на русский
/// по коду и подставляет аргументы (csharp-school-frontend: compiler.* в src/i18n/locales),
/// поэтому порядок аргументов здесь — договорённость с фронтом.
/// </summary>
public sealed class CommonMistakesTests
{
    private static readonly RunnerService Service = new();

    public static TheoryData<string, string, string[]> Mistakes => new()
    {
        { "System.Console.WriteLine(\"Привет\")", "CS1002", [] },
        { "int hp = 10;\nSystem.Console.WriteLine(HP);", "CS0103", ["HP"] },
        { "Strin name = \"Аня\";", "CS0246", ["Strin"] },
        { "int hp = \"сто\";", "CS0029", ["string", "int"] },
        { "int hp = 1.5;", "CS0266", ["double", "int"] },
        { "int hp;\nSystem.Console.WriteLine(hp);", "CS0165", ["hp"] },
        { "int hp = 1;\nint hp = 2;", "CS0128", ["hp"] },
        { "System.Console.WriteLine(\"Привет);", "CS1010", [] },
        { "if (true)\n{\n    System.Console.WriteLine(1);", "CS1513", [] },
        { "System.Console.WriteLine(\"Привет\";", "CS1026", [] },
        { "System.Console.Writeline(\"Привет\");", "CS0117", ["Console", "Writeline"] },
        { "string name = \"Аня\";\nint length = name.Lenght;", "CS1061", ["string", "Lenght"] },
        { "int damage = \"10\" - 1;", "CS0019", ["-", "string", "int"] },
        { "int max = System.Math.Max(1);", "CS1501", ["Max", "1"] },
        { "int Damage()\n{\n}\nSystem.Console.WriteLine(Damage());", "CS0161", ["Damage()"] },
        { "1 + 1;", "CS0201", [] },
        { "int hp = ;", "CS1525", [";"] },
        { "int unused = 5;", "CS0219", ["unused"] },
        { "int hp;", "CS0168", ["hp"] },
        { "namespace Game;\nclass Hero { }", "CS8773", ["file-scoped namespace", "10.0"] },
        { "class Hero { }\nSystem.Console.WriteLine(1);", "CS8803", [] },
        { "class Hero { }", "CS5001", [] },
    };

    [Theory]
    [MemberData(nameof(Mistakes))]
    public void ReportsCodeAndArguments(string code, string expectedCode, string[] expectedArguments)
    {
        var diagnostics = Service.Compile(new CompileRequest(code)).Diagnostics;

        var diagnostic = Assert.Single(diagnostics, d => d.Code == expectedCode);
        Assert.Equal(expectedArguments, diagnostic.Arguments);
    }

    [Fact]
    public void WarningsDoNotStopCompilation()
    {
        var response = Service.Compile(new CompileRequest("int unused = 5;"));

        Assert.True(response.Compiled);
        Assert.Equal(DiagnosticLevel.Warning, Assert.Single(response.Diagnostics).Severity);
    }

    [Fact]
    public void PositionsStartFromOne()
    {
        // HP — третья строка, с 19-го по 20-й символ (конец — позиция после последнего символа)
        var diagnostics = Service.Compile(new CompileRequest("using System;\nint hp = 10;\nConsole.WriteLine(HP);")).Diagnostics;
        var diagnostic = Assert.Single(diagnostics, d => d.Code == "CS0103");

        Assert.Equal((3, 19, 3, 21), (diagnostic.Line, diagnostic.Column, diagnostic.EndLine, diagnostic.EndColumn));
    }

    [Fact]
    public void CompilesLikeUnityWithoutImplicitUsings()
    {
        // Как в скриптах Unity: using System; пишется явно
        var diagnostic = Assert.Single(Service.Compile(new CompileRequest("Console.WriteLine(1);")).Diagnostics);

        Assert.Equal(("CS0103", "Console"), (diagnostic.Code, diagnostic.Arguments[0]));
    }
}
