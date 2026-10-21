using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner.Tests;

/// <summary>
/// Частые ошибки новичка: код ошибки компилятора и её аргументы. Фронт переводит их на русский
/// по коду и подставляет аргументы (frontend: compiler.* в src/i18n/locales),
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
        { "double weight = 0,3;", "CS1001", [] },
        // Методы (локальные функции после основного кода) и циклы
        { "int Damage(int a, int b) { return a + b; }\nSystem.Console.WriteLine(Damage(1));", "CS7036", ["b", "Damage(int, int)"] },
        { "void Show(int hp, string name) { }\nShow(1, 2);", "CS1503", ["2", "int", "string"] },
        { "int hp = 5;\nSystem.Console.WriteLine(Twice());\nstatic int Twice() { return hp * 2; }", "CS8421", ["hp"] },
        { "Hit();\nvoid Hit() { return 5; }", "CS0127", ["Hit()"] },
        { "System.Console.WriteLine(Hit());\nint Hit() { return; }", "CS0126", ["int"] },
        { "Hit();\npublic void Hit() { }", "CS0106", ["public"] },
        { "int x = Hit;\nSystem.Console.WriteLine(x);\nint Hit() { return 1; }", "CS0428", ["Hit", "int"] },
        { "int i = 0;\nfor (int i = 0; i < 3; i++) { }\nSystem.Console.WriteLine(i);", "CS0136", ["i"] },
        { "System.Console.WriteLine(1);\nvoid Unused() { }", "CS8321", ["Unused"] },
        { "System.Console.WriteLine(Score(1));\nint Score(int a) { if (a > 0) { return 1; } }", "CS0161", ["Score(int)"] },
        // void-метод там, где нужно значение: особый текст на фронте по аргументу «void»
        { "int x = Hello();\nvoid Hello() { }", "CS0029", ["void", "int"] },
        { "Show(Hello());\nvoid Hello() { }\nvoid Show(int a) { }", "CS1503", ["1", "void", "int"] },
        { "var x = Hello();\nvoid Hello() { }", "CS0815", ["void"] },
        { "System.Console.WriteLine($\"{Hello()}\");\nvoid Hello() { }", "CS8773", ["interpolated string handlers", "10.0"] },
    };

    [Theory]
    [MemberData(nameof(Mistakes))]
    public void ReportsCodeAndArguments(string code, string expectedCode, string[] expectedArguments)
    {
        var diagnostics = Service.Compile(new CompileRequest(code)).Diagnostics;

        var diagnostic = Assert.Single(diagnostics, d => d.Code == expectedCode);
        Assert.Equal(expectedArguments, diagnostic.Arguments);
    }

    [Theory]
    [InlineData("System.Console.WriteLine($\"Итог: {Hello()}\");\nvoid Hello() { }", "void")]
    [InlineData("System.Console.WriteLine($\"Итог: {Hello}\");\nint Hello() => 1;", "method group")]
    public void InterpolationHoleErrorLiesInsideFeatureError(string code, string source)
    {
        // Фронт узнаёт «void в $"{…}"» по CS1503 с типом void внутри места CS8773
        var diagnostics = Service.Compile(new CompileRequest(code)).Diagnostics;
        var feature = Assert.Single(diagnostics, d => d.Code == "CS8773");
        var hole = Assert.Single(diagnostics, d => d.Code == "CS1503");

        Assert.Equal(source, hole.Arguments[1]);
        Assert.True((feature.Line, feature.Column).CompareTo((hole.Line, hole.Column)) <= 0, "CS1503 начинается раньше CS8773");
        Assert.True((hole.EndLine, hole.EndColumn).CompareTo((feature.EndLine, feature.EndColumn)) <= 0, "CS1503 кончается позже CS8773");
    }

    [Fact]
    public void LocalFunctionNamesAreReadable()
    {
        // Без внутренних имён компилятора вроде <<Main>$>g__Damage|0_0
        const string code = "int x = Damage(1);\nDamage(1, \"a\");\nvoid Hit() { return 1; }\nint Damage(int a, int b) { if (a > 0) { return 1; } }\nvoid Unused() { }";

        var arguments = Service.Compile(new CompileRequest(code)).Diagnostics.SelectMany(d => d.Arguments).ToList();

        Assert.Contains("Damage(int, int)", arguments);
        Assert.Contains("Unused", arguments);
        Assert.DoesNotContain(arguments, argument => argument.Contains('<', StringComparison.Ordinal) || argument.Contains('$', StringComparison.Ordinal));
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
