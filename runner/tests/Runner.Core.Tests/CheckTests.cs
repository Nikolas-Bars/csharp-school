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
