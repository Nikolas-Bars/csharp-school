using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner.Tests;

public sealed class CheckTests
{
    private static readonly RunnerService Service = new();

    private static TestResponse Test(string code, params Check[] checks) => Service.Test(new TestRequest(code, checks));

    [Fact]
    public void OutputMatchesIgnoringLineEndingsAndTrailingSpaces()
    {
        var response = Test("using System;\nConsole.Write(\"HP: 100  \\r\\nУрон: 5\\n\\n\");", new OutputCheck("HP: 100\nУрон: 5"));

        Assert.True(response.Passed);
    }

    [Fact]
    public void WrongOutputShowsWhatWasPrinted()
    {
        var result = Assert.Single(Test("System.Console.Write(\"HP: 99\");", new OutputCheck("HP: 100")).Results);

        Assert.False(result.Passed);
        Assert.Equal(("HP: 100", "HP: 99"), (result.Expected, result.Actual));
    }

    [Fact]
    public void OutputContainsAndEachCheckHasItsOwnInput()
    {
        const string code = "using System;\nint hp = int.Parse(Console.ReadLine());\nConsole.WriteLine(hp > 0 ? \"Жив\" : \"Побеждён\");";

        var response = Test(
            code,
            new OutputCheck("Жив", Stdin: "10", Match: OutputMatch.Contains),
            new OutputCheck("Побеждён", Stdin: "0"));

        Assert.True(response.Passed);
    }

    [Fact]
    public void CrashFailsOutputCheck()
    {
        var result = Assert.Single(Test("int zero = 0;\nSystem.Console.Write(1 / zero);", new OutputCheck("0")).Results);

        Assert.False(result.Passed);
        Assert.Equal("DivideByZeroException", result.Error?.Type);
    }

    [Fact]
    public void VariableNameIsCaseSensitiveAndTypeIsInferred()
    {
        var response = Test(
            "var hp = 100;\nstring HeroName = \"Аня\";",
            new VariableCheck("hp", "int"),
            new VariableCheck("HP"),
            new VariableCheck("HeroName", "int"));

        Assert.Equal([true, false, false], response.Results.Select(result => result.Passed));
        Assert.Equal("string", response.Results[2].Actual);
    }

    [Fact]
    public void ForeachVariableAndGenericTypesCount()
    {
        var response = Test(
            "using System.Collections.Generic;\nvar items = new List<string> { \"меч\" };\nforeach (var item in items) { }",
            new VariableCheck("items", "List<string>"),
            new VariableCheck("item", "string"));

        Assert.True(response.Passed);
    }

    [Fact]
    public void ConstructsAreCounted()
    {
        var response = Test(
            "for (int i = 0; i < 3; i++) { if (i == 1) { } else { } }",
            new ConstructCheck(Construct.For),
            new ConstructCheck(Construct.If, Min: 2),
            new ConstructCheck(Construct.Else));

        Assert.Equal([true, false, true], response.Results.Select(result => result.Passed));
        Assert.Equal(("2", "1"), (response.Results[1].Expected, response.Results[1].Actual));
    }

    [Fact]
    public void BreakCountsOnlyWhenItLeavesLoopAndContinueAlways()
    {
        const string code = """
            for (int i = 0; i < 5; i++)
            {
                switch (i)
                {
                    case 1:
                        continue;
                    default:
                        break;
                }

                while (true)
                {
                    break;
                }
            }
            """;

        var response = Test(
            code,
            new ConstructCheck(Construct.Break),
            new ConstructCheck(Construct.Break, Min: 2),
            new ConstructCheck(Construct.Continue));

        Assert.Equal([true, false, true], response.Results.Select(result => result.Passed));
        Assert.Equal("1", response.Results[1].Actual);
    }

    [Fact]
    public void NoBreakOrContinueInCode()
    {
        var response = Test(
            "switch (1) { case 1: break; }",
            new ConstructCheck(Construct.Break),
            new ConstructCheck(Construct.Continue));

        Assert.Equal([false, false], response.Results.Select(result => result.Passed));
        Assert.Equal("0", response.Results[0].Actual);
    }

    [Fact]
    public void CallFromCallerCountsOnlyInsideThatMethod()
    {
        const string code = """
            using System;
            Report(5);
            Console.WriteLine(GetStatus(1));

            void Report(int hp)
            {
                Func<string> later = () => GetStatus(hp);
                Console.WriteLine(later());
            }

            string GetStatus(int hp)
            {
                return hp > 0 ? "Жив" : "Побеждён";
            }
            """;

        var response = Test(
            code,
            new CallCheck("Program.GetStatus"),
            new CallCheck("Program.GetStatus", Caller: "Program.Report"),
            new CallCheck("Program.Report", Caller: "Program.GetStatus"),
            new CallCheck("System.Console.WriteLine", Caller: "Program.Report"));

        Assert.Equal([true, true, false, true], response.Results.Select(result => result.Passed));
        Assert.Equal(("2", "1"), (response.Results[0].Actual, response.Results[1].Actual));
    }

    [Fact]
    public void CallFromNestedMethodCountsForOuterCaller()
    {
        const string code = """
            Report();

            void Report()
            {
                void Inner()
                {
                    GetStatus();
                }

                Inner();
            }

            string GetStatus() => "Жив";
            """;

        var response = Test(
            code,
            new CallCheck("Program.GetStatus", Caller: "Program.Report"),
            new CallCheck("Program.GetStatus", Caller: "Program.Inner"));

        Assert.True(response.Passed);
    }

    [Fact]
    public void ExecutionEndIsReportedAfterEachRun()
    {
        var events = new List<string>();
        var response = Service.Test(
            new TestRequest("int height = 3;\nSystem.Console.Write(height);", [
                new OutputCheck("3"),
                new OutputCheck("5", Set: new Dictionary<string, System.Text.Json.JsonElement> { ["height"] = System.Text.Json.JsonSerializer.SerializeToElement(5) }),
                new ConstructCheck(Construct.For),
            ]),
            () => events.Add("start"),
            () => events.Add("end"));

        Assert.Equal([true, true, false], response.Results.Select(result => result.Passed));
        Assert.Equal(["start", "end", "start", "end"], events);
    }

    [Fact]
    public void CallFromMainCodeDoesNotCountForCaller()
    {
        var response = Test(
            "int Twice(int a) => a * 2;\nint Show() => 1;\nSystem.Console.Write(Twice(Show()));",
            new CallCheck("Program.Twice", Caller: "Program.Show"));

        Assert.False(response.Passed);
    }

    [Fact]
    public void CallsAreFoundBySymbolNotByText()
    {
        var response = Test(
            "using System;\nConsole.WriteLine(Math.Max(1, 2));",
            new CallCheck("System.Console.WriteLine"),
            new CallCheck("System.Math.Max"),
            new CallCheck("System.Math.Min"));

        Assert.Equal([true, true, false], response.Results.Select(result => result.Passed));
    }

    [Fact]
    public void NothingIsCheckedWhenCodeDoesNotCompile()
    {
        var response = Test("int hp = ", new VariableCheck("hp"));

        Assert.False(response.Compiled);
        Assert.False(response.Passed);
        Assert.Empty(response.Results);
    }
}
