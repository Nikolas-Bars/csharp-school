using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner.Tests;

/// <summary>Тесты, которые сами подменяют Console: идут отдельно от остальных, не параллельно.</summary>
[CollectionDefinition(nameof(ConsoleTests), DisableParallelization = true)]
[Collection(nameof(ConsoleTests))]
public sealed class ConsoleTests
{
    [Fact]
    public void ReportsExecutionStartAndGivesConsoleBack()
    {
        var originalOut = Console.Out;
        var ours = new StringWriter();
        var started = 0;
        Console.SetOut(ours);

        try
        {
            new RunnerService().Run(new RunRequest("System.Console.Write(\"ученик\");"), () => started++);
            Console.Write("после");
        }
        finally
        {
            Console.SetOut(originalOut);
        }

        Assert.Equal(1, started);
        Assert.Equal("после", ours.ToString());
    }
}
