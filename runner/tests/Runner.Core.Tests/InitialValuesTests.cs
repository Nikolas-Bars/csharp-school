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

    [Theory]
    [InlineData("int[] items = { 1, 2 };", """{ "items": [5, 9, 1] }""", "5,9,1")]
    [InlineData("int[] items = new int[] { 1, 2 };", """{ "items": [5, -9] }""", "5,-9")]
    // Длина в скобках не мешает: подставляется new int[] { … } целиком
    [InlineData("int[] items = new int[2] { 1, 2 };", """{ "items": [5, 9, 1] }""", "5,9,1")]
    [InlineData("int[] items = new int[2];", """{ "items": [7] }""", "7")]
    [InlineData("var items = new[] { 1, 2 };", """{ "items": [3] }""", "3")]
    [InlineData("int[] items = { 1, 2 };", """{ "items": [] }""", "")]
    [InlineData("long[] items = { 1 };", """{ "items": [5000000000] }""", "5000000000")]
    [InlineData("double[] items = { 1.5 };", """{ "items": [2.25, 3] }""", "2.25,3")]
    [InlineData("float[] items = { 1.5f };", """{ "items": [0.5] }""", "0.5")]
    [InlineData("decimal[] items = { 1.5m };", """{ "items": [9.99] }""", "9.99")]
    [InlineData("string[] items = { \"Аня\" };", """{ "items": ["Боб", "\"Меч\""] }""", "Боб,\"Меч\"")]
    [InlineData("char[] items = { 'a' };", """{ "items": ["W", "S"] }""", "W,S")]
    [InlineData("bool[] items = { true };", """{ "items": [false, true] }""", "False,True")]
    [InlineData("List<int> items = new List<int> { 1, 2 };", """{ "items": [5, 9, 1] }""", "5,9,1")]
    [InlineData("List<string> items = new List<string>();", """{ "items": ["Аня"] }""", "Аня")]
    [InlineData("var items = new List<int>();", """{ "items": [] }""", "")]
    // Без using System.Collections.Generic: подставляется полное имя типа
    [InlineData("System.Collections.Generic.List<int> items = new System.Collections.Generic.List<int>();", """{ "items": [4] }""", "4")]
    public void ReplacesArrayAndList(string declaration, string set, string printed)
    {
        var result = Check($"using System.Collections.Generic;\n{declaration}\nSystem.Console.Write(string.Join(\",\", items));", printed, set);

        Assert.True(result.Passed, result.Actual);
        Assert.Null(result.SetError);
    }

    [Theory]
    [InlineData("double[] items = { 1.5 };")]
    [InlineData("var items = new List<double> { 1.5 };")]
    public void WholeNumberInArrayForDoubleStaysDouble(string declaration)
    {
        var result = Check($"using System.Collections.Generic;\n{declaration}\nSystem.Console.Write(items[0] / 2);", "1.5", """{ "items": [3] }""");

        Assert.True(result.Passed, result.Actual);
    }

    [Fact]
    public void SumOverArrayIsCheckedWithAnotherArray()
    {
        const string loop = "int[] scores = { 3, 4 };\nint sum = 0;\nforeach (int score in scores)\n{\n    sum += score;\n}\nSystem.Console.Write(sum);";
        const string printed = "int[] scores = { 3, 4 };\nforeach (int score in scores) { }\nSystem.Console.Write(7);";

        Assert.True(Check(loop, "15", """{ "scores": [5, 9, 1] }""").Passed);
        Assert.False(Check(printed, "15", """{ "scores": [5, 9, 1] }""").Passed);
    }

    [Fact]
    public void LineOfExceptionStaysTheSameAfterMultilineArray()
    {
        const string code = "int[] scores =\n{\n    1,\n    2,\n};\nint[] empty = new int[0];\nSystem.Console.Write(empty[scores.Length]);";

        var result = Check(code, "0", """{ "scores": [5, 9, 1] }""");

        Assert.Equal(("IndexOutOfRangeException", 7), (result.Error?.Type, result.Error?.Line));
    }

    [Theory]
    [InlineData("int[] items = { 1 };", """{ "items": 5 }""", "int[]")]
    [InlineData("int[] items = { 1 };", """{ "items": [1.5] }""", "int[]")]
    [InlineData("int[] items = { 1 };", """{ "items": [1, "2"] }""", "int[]")]
    [InlineData("int[] items = { 1 };", """{ "items": [null] }""", "int[]")]
    [InlineData("char[] items = { 'a' };", """{ "items": ["ab"] }""", "char[]")]
    [InlineData("int[,] items = new int[1, 1];", """{ "items": [1] }""", "int[,]")]
    [InlineData("int[][] items = new int[1][];", """{ "items": [[1]] }""", "int[][]")]
    [InlineData("List<int> items = new List<int>();", """{ "items": "1" }""", "List<int>")]
    [InlineData("List<List<int>> items = new List<List<int>>();", """{ "items": [[1]] }""", "List<List<int>>")]
    [InlineData("Dictionary<string, int> items = new Dictionary<string, int>();", """{ "items": [1] }""", "Dictionary<string, int>")]
    [InlineData("int items = 1;", """{ "items": [1] }""", "int")]
    public void WrongArrayValueIsReported(string declaration, string set, string type)
    {
        var result = Check($"using System.Collections.Generic;\n{declaration}\nSystem.Console.Write(items);", "1", set);

        Assert.False(result.Passed);
        Assert.Equal(new SetError("items", SetProblem.WrongType, type), result.SetError);
    }

    [Theory]
    // Пустой [] не проверяет ни одного элемента: тип элемента проверяется отдельно
    [InlineData("int[][] items = new int[1][];", "int[][]")]
    [InlineData("Hero[] items = new Hero[1];", "Hero[]")]
    [InlineData("List<List<int>> items = new List<List<int>>();", "List<List<int>>")]
    [InlineData("List<Hero> items = new List<Hero>();", "List<Hero>")]
    public void EmptyArrayForNotSimpleElementsIsWrongType(string declaration, string type)
    {
        var result = Check($"using System.Collections.Generic;\n{declaration}\nSystem.Console.Write(items);\nclass Hero {{ }}", "1", """{ "items": [] }""");

        Assert.Equal(new SetError("items", SetProblem.WrongType, type), result.SetError);
    }

    [Fact]
    public void LineOfExceptionStaysTheSameWithWindowsLineEndings()
    {
        const string code = "int[] scores =\r\n{\r\n    1,\r\n    2,\r\n};\r\nint[] empty = new int[0];\r\nSystem.Console.Write(empty[scores.Length]);";

        var result = Check(code, "0", """{ "scores": [5, 9, 1] }""");

        Assert.Equal(("IndexOutOfRangeException", 7), (result.Error?.Type, result.Error?.Line));
    }

    [Theory]
    [InlineData("string[] items = { \"a\" };", """{ "items": ["a\"b", "c\\d", "{0}", "e'f", "x\ty", "/* */"] }""", "a\"b|c\\d|{0}|e'f|x\ty|/* */")]
    [InlineData("char[] items = { 'a' };", """{ "items": ["'", "\\", "\"", "й", "\t"] }""", "'|\\|\"|й|\t")]
    public void SpecialCharactersInArrayAreEscaped(string declaration, string set, string printed)
    {
        var result = Check($"{declaration}\nSystem.Console.Write(string.Join(\"|\", items));", printed, set);

        Assert.True(result.Passed, result.Actual);
    }

    [Fact]
    public void ArrayInLoopOrWithoutValueIsReported()
    {
        Assert.Equal(
            new SetError("items", SetProblem.NotFound),
            Check("for (int i = 0; i < 1; i++) { int[] items = { 1 }; System.Console.Write(items[0]); }", "5", """{ "items": [5] }""").SetError);
        Assert.Equal(
            new SetError("items", SetProblem.NoValue),
            Check("int[] items;\nitems = new int[] { 1 };\nSystem.Console.Write(items[0]);", "5", """{ "items": [5] }""").SetError);
    }

    [Theory]
    // Ничего не печатает — и ничего не ждали: пройдено
    [InlineData("int count = 0;", """{ "count": 0 }""", true)]
    [InlineData("int count = 3;", """{ "count": 0 }""", true)]
    [InlineData("int count = 0;", """{ "count": 2 }""", false)]
    public void EmptyExpectedOutputMeansProgramPrintsNothing(string declaration, string set, bool passed)
    {
        var code = $"{declaration}\nfor (int i = 0; i < count; i++)\n{{\n    System.Console.WriteLine(i);\n}}";

        Assert.Equal(passed, Check(code, string.Empty, set).Passed);
        Assert.True(Service.Test(new TestRequest("int count = 0;", [new OutputCheck(string.Empty)])).Passed);
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
