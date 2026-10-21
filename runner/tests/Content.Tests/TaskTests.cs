using CSharpSchool.Runner;
using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Content.Tests;

/// <summary>Каждая задача решаема, нерешённая не засчитывается, типичные ошибки ловятся (rules/course-content.md)</summary>
public sealed class TaskTests
{
    private static readonly RunnerService Runner = new();

    private static TestResponse Test(string task, string file) =>
        Runner.Test(new TestRequest(Course.Text(task, file), Course.Task(task).Checks));

    [Theory]
    [MemberData(nameof(Course.Tasks), MemberType = typeof(Course))]
    public void SolutionPassesAllChecks(string task)
    {
        var response = Test(task, Course.Task(task).Solution);

        Assert.True(response.Compiled, string.Join("\n", response.Diagnostics.Select(d => $"{d.Code}: {d.Message}")));
        Assert.All(response.Results, result => Assert.True(result.Passed, $"проверка {result.Index}: ожидалось «{result.Expected}», получилось «{result.Actual}»{SetError(result)}"));
    }

    /// <summary>set в проверке вывода не применился: нет переменной, нет значения или не тот тип</summary>
    private static string SetError(CheckResult result) =>
        result.SetError is { } error ? $"; set не применился: {error.Variable} — {error.Problem} {error.Type}" : string.Empty;

    [Theory]
    [MemberData(nameof(Course.Tasks), MemberType = typeof(Course))]
    public void StarterCompilesButIsNotASolution(string task)
    {
        var response = Test(task, Course.Task(task).Starter);
        var errors = response.Diagnostics.Where(d => d.Severity == DiagnosticLevel.Error).Select(d => d.Code).ToList();

        // Пустая программа (только using и комментарий) — CS5001 «нет команд»: для первых задач это нормальный старт
        Assert.True(errors.All(code => code == "CS5001"), "стартовый код не компилируется: " + string.Join(", ", errors));
        Assert.False(response.Passed, "стартовый код уже решает задачу");
    }

    [Theory]
    [MemberData(nameof(Course.WrongSolutions), MemberType = typeof(Course))]
    public void WrongSolutionFailsAsExpected(string task, string file)
    {
        var wrong = Course.Task(task).Wrong.Single(w => w.File == file);
        var response = Test(task, file);

        if (wrong.CompileError is not null)
        {
            Assert.False(response.Compiled, $"{wrong.Why}: ожидалась ошибка {wrong.CompileError}, а код скомпилировался");
            Assert.Contains(wrong.CompileError, response.Diagnostics.Select(d => d.Code));
            return;
        }

        Assert.True(response.Compiled, string.Join("\n", response.Diagnostics.Select(d => $"{d.Code}: {d.Message}")));
        Assert.Equal(wrong.Fails, [.. response.Results.Where(r => !r.Passed).Select(r => r.Index)]);
    }
}
