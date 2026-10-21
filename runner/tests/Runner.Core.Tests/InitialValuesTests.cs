using System.Text.Json;
using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner.Tests;

/// <summary>Проверка вывода с другими начальными значениями (set): пустой цикл и готовый ответ не проходят.</summary>
public sealed class InitialValuesTests
{
    private static readonly RunnerService Service = new();

    private static Dictionary<string, JsonElement> Set(string json)
    {
        using var document = JsonDocument.Parse(json);
        return document.RootElement.EnumerateObject().ToDictionary(property => property.Name, property => property.Value.Clone());
    }

    private static CheckResult Check(string code, string expected, string set) =>
        Assert.Single(Service.Test(new TestRequest(code, [new OutputCheck(expected, Set: Set(set))])).Results);

    [Fact]
    public void LoopIsCheckedWithAnotherValue()
    {
        const string code = "using System;\nint height = 3;\nfor (int i = 1; i <= height; i++)\n{\n    Console.WriteLine(new string('#', i));\n}";

        var response = Service.Test(new TestRequest(code, [
            new OutputCheck("#\n##\n###"),
            new OutputCheck("#\n##\n###\n####", Set: Set("""{ "height": 4 }""")),
        ]));

        Assert.True(response.Passed);
    }

    [Fact]
    public void PrintedAnswerFailsWithAnotherValue()
    {
        // Пустой цикл ради проверки construct и готовый ответ
        const string code = "using System;\nint height = 3;\nfor (int i = 0; i < 0; i++) { }\nConsole.WriteLine(\"#\\n##\\n###\");";

        var result = Check(code, "#\n##\n###\n####", """{ "height": 4 }""");

        Assert.False(result.Passed);
        Assert.Null(result.SetError);
        Assert.Equal("#\n##\n###\n", result.Actual?.ReplaceLineEndings("\n"));
    }

    [Theory]
    [InlineData("int hp = 100;", """{ "hp": -5 }""", "-5")]
    [InlineData("long gold = 1;", """{ "gold": 5000000000 }""", "5000000000")]
    [InlineData("double speed = 1.5;", """{ "speed": 2.25 }""", "2.25")]
    [InlineData("double speed = 1.5;", """{ "speed": 3 }""", "3")]
    [InlineData("float speed = 1.5f;", """{ "speed": 0.5 }""", "0.5")]
    [InlineData("decimal price = 1.5m;", """{ "price": 9.99 }""", "9.99")]
    [InlineData("string name = \"Аня\";", """{ "name": "Боб \"Меч\"\n" }""", "Боб \"Меч\"")]
    [InlineData("char key = 'a';", """{ "key": "W" }""", "W")]
    [InlineData("bool alive = true;", """{ "alive": false }""", "False")]
    [InlineData("var level = 1;", """{ "level": 7 }""", "7")]
    [InlineData("const int max = 3;", """{ "max": 10 }""", "10")]
    public void ReplacesLiteralOfEachSimpleType(string declaration, string set, string printed)
    {
        var name = declaration[..declaration.IndexOf('=', StringComparison.Ordinal)].Trim().Split(' ')[^1];
        var result = Check($"{declaration}\nSystem.Console.Write({name});", printed, set);

        Assert.True(result.Passed, result.Actual);
    }

    [Theory]
    [InlineData("""{ "speed": 3 }""", "1.5")]
    [InlineData("""{ "speed": -4 }""", "-2")]
    [InlineData("""{ "speed": 1e3 }""", "500")]
    public void WholeNumberForDoubleStaysDouble(string set, string printed)
    {
        // Если бы подставилось «var speed = 3;», тип стал бы int: деление нацело, а speed * 1.5 не собрался бы
        var result = Check("var speed = 1.5;\nspeed = speed * 1.0;\nSystem.Console.Write(speed / 2);", printed, set);

        Assert.True(result.Passed, result.Actual);
    }

    [Fact]
    public void CodeThatDoesNotCompileWithNewValueIsReported()
    {
        var result = Check(
            "const int size = 3;\nint[] items = new int[size] { 1, 2, 3 };\nSystem.Console.Write(items.Length);",
            "4",
            """{ "size": 4 }""");

        Assert.False(result.Passed);
        Assert.Equal(new SetError("size", SetProblem.NotCompiled), result.SetError);
    }

    [Fact]
    public void BrokenTextIsWrongTypeNotException()
    {
        // Половинка суррогатной пары — не текст: GetString() бросил бы исключение
        var result = Check("string name = \"Аня\";\nSystem.Console.Write(name);", "?", """{ "name": "\ud800" }""");

        Assert.Equal(new SetError("name", SetProblem.WrongType, "string"), result.SetError);
    }

    [Fact]
    public void ReplacesSeveralVariablesAndOnlyTheNamedOneInMultiDeclaration()
    {
        var result = Check("int width = 2, height = 3;\nint area = width * height;\nSystem.Console.Write(area);", "10", """{ "width": 5, "height": 2 }""");

        Assert.True(result.Passed, result.Actual);
    }

    [Fact]
    public void LineOfExceptionStaysTheSame()
    {
        var result = Check("int zero = 1;\nint[] items = new int[2];\nSystem.Console.Write(items[zero * 5]);", "0", """{ "zero": 1 }""");

        Assert.Equal(("IndexOutOfRangeException", 3), (result.Error?.Type, result.Error?.Line));
    }

    [Theory]
    [InlineData("int h = 3;\nSystem.Console.Write(h);")]
    // Внутри цикла, блока и метода — не основной код: не подменяется
    [InlineData("for (int i = 0; i < 1; i++) { int height = 3; System.Console.Write(height); }")]
    [InlineData("{ int height = 3; System.Console.Write(height); }")]
    [InlineData("Show();\nvoid Show() { int height = 3; System.Console.Write(height); }")]
    [InlineData("class Program { static void Main() { int height = 3; System.Console.Write(height); } }")]
    public void VariableOutsideMainCodeIsNotFound(string code)
    {
        var result = Check(code, "5", """{ "height": 5 }""");

        Assert.False(result.Passed);
        Assert.Equal(new SetError("height", SetProblem.NotFound), result.SetError);
        Assert.Null(result.Actual);
    }

    [Fact]
    public void VariableWithoutValueIsReported()
    {
        var result = Check("int height;\nheight = 3;\nSystem.Console.Write(height);", "5", """{ "height": 5 }""");

        Assert.Equal(new SetError("height", SetProblem.NoValue), result.SetError);
    }

    [Theory]
    [InlineData("int height = 3;", """{ "height": 2.5 }""", "int")]
    [InlineData("int height = 3;", """{ "height": "5" }""", "int")]
    [InlineData("int height = 3;", """{ "height": 5000000000 }""", "int")]
    [InlineData("int height = 3;", """{ "height": 5.0 }""", "int")]
    [InlineData("string height = \"3\";", """{ "height": 5 }""", "string")]
    [InlineData("bool height = true;", """{ "height": 1 }""", "bool")]
    [InlineData("char height = 'a';", """{ "height": "ab" }""", "char")]
    [InlineData("byte height = 3;", """{ "height": 5 }""", "byte")]
    [InlineData("int height = 3;", """{ "height": null }""", "int")]
    [InlineData("System.Random height = new System.Random(42);", """{ "height": 7 }""", "Random")]
    public void WrongTypeIsReported(string declaration, string set, string type)
    {
        var result = Check($"{declaration}\nSystem.Console.Write(height);", "5", set);

        Assert.False(result.Passed);
        Assert.Equal(new SetError("height", SetProblem.WrongType, type), result.SetError);
    }

    [Fact]
    public void SeedOfRandomGoesThroughItsOwnVariable()
    {
        const string code = "using System;\nint seed = 42;\nRandom random = new Random(seed);\nConsole.Write(random.Next(100));";
        var expected = new Random(7).Next(100).ToString(System.Globalization.CultureInfo.InvariantCulture);

        Assert.True(Check(code, expected, """{ "seed": 7 }""").Passed);
    }

    [Fact]
    public void StudentCodeIsNotChangedOutsideTheCheck()
    {
        const string code = "int height = 3;\nSystem.Console.Write(height);";

        var response = Service.Test(new TestRequest(code, [
            new OutputCheck("5", Set: Set("""{ "height": 5 }""")),
            new OutputCheck("3"),
        ]));

        Assert.True(response.Passed);
        Assert.Equal("3", Service.Run(new RunRequest(code)).Output);
    }
}
