using System.Text.Json;
using System.Text.Json.Serialization.Metadata;
using CSharpSchool.Runner.Checks;
using CSharpSchool.Runner.Compilation;
using CSharpSchool.Runner.Execution;
using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner;

/// <summary>
/// Вход в runner: compile, run, test. Браузерная оболочка вызывает *Json-методы
/// (запрос и ответ — JSON протокола), тесты и будущие проверки курса — типизированные.
/// </summary>
public sealed class RunnerService(int outputLimit = ProgramRunner.DefaultOutputLimit)
{
    private readonly StudentCompiler compiler = new();
    private readonly ProgramRunner runner = new(outputLimit);

    public CompileResponse Compile(CompileRequest request)
    {
        var program = compiler.Compile(request.Code, emit: false);
        var compiled = program.Diagnostics.All(diagnostic => diagnostic.Severity != DiagnosticLevel.Error);

        return new CompileResponse(compiled, program.Diagnostics);
    }

    public RunResponse Run(RunRequest request, Action? onExecutionStarted = null)
    {
        var program = compiler.Compile(request.Code);

        if (!program.Compiled)
        {
            return new RunResponse(false, program.Diagnostics, string.Empty, false, null);
        }

        var result = runner.Run(program, request.Stdin, onExecutionStarted);

        return new RunResponse(true, program.Diagnostics, result.Output, result.OutputLimitExceeded, result.Error);
    }

    public TestResponse Test(TestRequest request, Action? onExecutionStarted = null)
    {
        var program = compiler.Compile(request.Code);

        if (!program.Compiled)
        {
            return new TestResponse(false, program.Diagnostics, false, []);
        }

        var results = new CheckRunner(runner).Run(program, request.Checks, onExecutionStarted);

        return new TestResponse(true, program.Diagnostics, results.All(result => result.Passed), results);
    }

    public string CompileJson(string request) =>
        Handle(request, RunnerJsonContext.Default.CompileRequest, RunnerJsonContext.Default.CompileResponse, Compile);

    public string RunJson(string request, Action? onExecutionStarted = null) =>
        Handle(request, RunnerJsonContext.Default.RunRequest, RunnerJsonContext.Default.RunResponse, r => Run(r, onExecutionStarted));

    public string TestJson(string request, Action? onExecutionStarted = null) =>
        Handle(request, RunnerJsonContext.Default.TestRequest, RunnerJsonContext.Default.TestResponse, r => Test(r, onExecutionStarted));

    private static string Handle<TRequest, TResponse>(
        string json,
        JsonTypeInfo<TRequest> requestType,
        JsonTypeInfo<TResponse> responseType,
        Func<TRequest, TResponse> handle)
    {
        var request = JsonSerializer.Deserialize(json, requestType) ?? throw new JsonException("Пустой запрос");

        return JsonSerializer.Serialize(handle(request), responseType);
    }
}
