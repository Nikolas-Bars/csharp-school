using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner.Tests;

public sealed class RunTests
{
    private static readonly RunnerService Service = new();

    private static RunResponse Run(string code, string? stdin = null, Action? onStart = null) =>
        Service.Run(new RunRequest(code, stdin), onStart);

    [Fact]
    public void PrintsOutput()
    {
        var response = Run("using System;\nConsole.WriteLine(\"Привет, мир!\");\nConsole.Write(2 + 2);");

        Assert.True(response.Compiled);
        Assert.Null(response.Error);
        Assert.Equal("Привет, мир!\n4", response.Output.ReplaceLineEndings("\n"));
    }

    [Fact]
    public void ReadsStdin()
    {
        var response = Run("using System;\nstring name = Console.ReadLine();\nint age = int.Parse(Console.ReadLine());\nConsole.Write($\"{name}: {age + 1}\");", "Аня\n14");

        Assert.Equal("Аня: 15", response.Output);
    }

    [Fact]
    public void ClassicMainWorksToo()
    {
        var response = Run("using System;\nclass Program\n{\n    static void Main()\n    {\n        Console.Write(\"Main\");\n    }\n}");

        Assert.Equal("Main", response.Output);
    }

    [Fact]
    public void ReportsExceptionWithStudentLine()
    {
        var response = Run("using System;\nConsole.WriteLine(\"до\");\nint zero = 0;\nint result = 10 / zero;");

        Assert.Equal("до\n", response.Output.ReplaceLineEndings("\n"));
        Assert.NotNull(response.Error);
        Assert.Equal(("DivideByZeroException", 4), (response.Error.Type, response.Error.Line));
    }

    [Fact]
    public void ExceptionInsideMethodPointsToThatLine()
    {
        var response = Run("int[] items = new int[3];\nint Take(int index)\n{\n    return items[index];\n}\nTake(5);");

        Assert.Equal(("IndexOutOfRangeException", 4), (response.Error?.Type, response.Error?.Line));
    }

    [Theory]
    [InlineData("int shields = items[\"щит\"];", "KeyNotFoundException")]
    [InlineData("items.Add(\"меч\", 2);", "ArgumentException")]
    public void DictionaryErrorsHaveTheirOwnTypes(string mistake, string type)
    {
        // Фронт объясняет их по типу (runtime.* в src/i18n/locales): нет ключа и Add с ключом, который уже есть
        var response = Run($"using System.Collections.Generic;\nDictionary<string, int> items = new Dictionary<string, int>();\nitems.Add(\"меч\", 1);\n{mistake}");

        Assert.Equal((type, 4), (response.Error?.Type, response.Error?.Line));
    }

    [Fact]
    public void StopsEndlessOutput()
    {
        var service = new RunnerService(outputLimit: 1000);

        var response = service.Run(new RunRequest("while (true) System.Console.Write(\"спам \");"));

        Assert.True(response.OutputLimitExceeded);
        Assert.Null(response.Error);
        Assert.Equal(1000, response.Output.Length);
    }

    [Fact]
    public void EachRunStartsFresh()
    {
        const string code = "using System;\nclass Counter { public static int Value; }\nclass Program { static void Main() { Counter.Value++; Console.Write(Counter.Value); } }";

        Assert.Equal("1", Run(code).Output);
        Assert.Equal("1", Run(code).Output);
    }

    [Fact]
    public void DoesNotRunWhenCompilationFails()
    {
        var started = false;

        var response = Run("System.Console.Write(1)", onStart: () => started = true);

        Assert.False(response.Compiled);
        Assert.False(started);
        Assert.Equal("CS1002", Assert.Single(response.Diagnostics).Code);
    }
}
