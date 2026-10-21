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
        // Аудит курса: синтаксис, предупреждения, массивы, foreach, классы
        { "int[] a = { 1, 2 3 };", "CS1003", [","] },
        // Забытая «;» после объявления — тоже CS1003 с «,» (CS1002 нет): особый текст на фронте
        { "int hp = 5\nSystem.Console.WriteLine(hp);", "CS1003", [","] },
        { "for (int i = 0; i < 3; i++)\n{\n    break;\n    System.Console.WriteLine(i);\n}", "CS0162", [] },
        { "int hp = 5;\nif (hp > 0);\n{\n    System.Console.WriteLine(hp);\n}", "CS0642", [] },
        { "for (int i = 0; i < 3; i++);\n{\n    System.Console.WriteLine(1);\n}", "CS0642", [] },
        { "int x = 5 / 0;\nSystem.Console.WriteLine(x);", "CS0020", [] },
        { "int hp = 5;\nbool dead = !hp;", "CS0023", ["!", "int"] },
        { "System.Console.WriteLine(hp);\nint hp = 5;", "CS0841", ["hp"] },
        { "char key = 'ab';", "CS1012", [] },
        { "float speed = 2.5;", "CS0664", ["F", "float"] },
        { "int[] a = new int[3];\na.Length = 4;", "CS0200", ["Array.Length"] },
        { "string name = \"abc\";\nname[0] = 'b';", "CS0200", ["string.this[int]"] },
        { "string name = \"Аня\";\nint n = name.Length();", "CS1955", ["string.Length"] },
        { "int[] a = { 1, 2 };\nforeach (int x in a)\n{\n    x = 5;\n}", "CS1656", ["x", "foreach iteration variable"] },
        { "Hit = 5;\nvoid Hit() { }", "CS1656", ["Hit", "method group"] },
        { "using System.Collections.Generic;\nList<int> l = new List<int>;", "CS1526", [] },
        { "int x = (int)\"5\";", "CS0030", ["string", "int"] },
        { "int[] a = new int[3] { 1, 2 };", "CS0847", ["3"] },
        { "int hp = 5;\nforeach (int x in hp) { }", "CS1579", ["int", "GetEnumerator"] },
        { "bool alive = true;\nif (alive = false) { }", "CS0665", [] },
        { "int hp = 5;\nif (hp => 5) { }", "CS1660", ["lambda expression", "bool"] },
        { "Hero hero = new Hero();\nhero.Hit();\nclass Hero { void Hit() { } }", "CS0122", ["Hero.Hit()"] },
        { "Hero hero = new Hero();\nSystem.Console.WriteLine(hero.hp);\nclass Hero { int hp = 1; }", "CS0122", ["Hero.hp"] },
        { "Hero.hp = 5;\nclass Hero { public int hp; }", "CS0120", ["Hero.hp"] },
        { "using System;\nusing Game;\nRandom random = null;\nConsole.WriteLine(random);\nnamespace Game { class Random { } }", "CS0104", ["Random", "Game.Random", "System.Random"] },
        // Классы и объекты (модуль 5)
        { "int hp = 5;\nSystem.Console.WriteLine(hp);\nclass Hero { public int Get() { return hp; } }", "CS8801", ["hp"] },
        { "void Show() { }\nShow();\nclass Hero { public void Run() { Show(); } }", "CS8801", ["Show"] },
        { "System.Console.WriteLine(1);\nclass Hero { public void Hero() { } }", "CS0542", ["Hero"] },
        { "Hero hero = new Hero();\nhero.Hp = 5;\nclass Hero { public int Hp { get; private set; } }", "CS0272", ["Hero.Hp"] },
        { "Hero hero = new Hero(1, 2);\nclass Hero { public Hero(int hp) { } }", "CS1729", ["Hero", "2"] },
        // Свой конструктор с параметром — и new Hero() без аргументов уже не работает
        { "Hero hero = new Hero();\nclass Hero { public Hero(int hp) { } }", "CS7036", ["hp", "Hero.Hero(int)"] },
        { "Hero hero = new Hero();\nSystem.Console.WriteLine(hero.hp);\nclass Hero { public int hp; }", "CS0649", ["Hero.hp", "0"] },
        { "Hero hero = new Hero(5);\nSystem.Console.WriteLine(hero.Get());\nclass Hero { int hp; public Hero(int hp) { hp = hp; } public int Get() { return this.hp; } }", "CS1717", [] },
        { "Hero hero = new Hero();\nhero.LevelUp();\nclass Hero { public static void LevelUp() { } }", "CS0176", ["Hero.LevelUp()"] },
        // Особые случаи на фронте: = вместо == там, где нужен bool; Length у списка и Count у массива
        { "int hp = 5;\nif (hp = 20) { }", "CS0029", ["int", "bool"] },
        { "int gold = 5;\nint price = 3;\nbool canBuy = gold = price;", "CS0029", ["int", "bool"] },
        { "using System.Collections.Generic;\nList<int> items = new List<int>();\nint n = items.Length;", "CS1061", ["List<int>", "Length"] },
        { "int[] items = { 1 };\nint n = items.Count;", "CS1061", ["int[]", "Count"] },
        // switch и const (модуль 1)
        { "int state = 0;\nswitch (state)\n{\n    case 0:\n        System.Console.WriteLine(0);\n    case 1:\n        System.Console.WriteLine(1);\n        break;\n}", "CS0163", ["case 0:"] },
        { "int state = 0;\nswitch (state)\n{\n    case 0:\n        System.Console.WriteLine(0);\n        break;\n    default:\n        System.Console.WriteLine(1);\n}", "CS8070", ["default:"] },
        { "const int maxHp = 100;\nmaxHp = 120;", "CS0131", [] },
        { "const int maxHp;", "CS0145", [] },
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
