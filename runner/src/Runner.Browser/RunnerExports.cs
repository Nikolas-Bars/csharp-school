using System.Runtime.InteropServices.JavaScript;
using System.Runtime.Versioning;
using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner.Browser;

/// <summary>
/// Методы, которые вызывает Web Worker фронта (src/runner/runner.worker.ts). Запрос и ответ —
/// JSON протокола; типы на TypeScript — src/runner/protocol.ts.
/// </summary>
[SupportedOSPlatform("browser")]
public static partial class RunnerExports
{
    private static readonly RunnerService Service = new();

    [JSExport]
    public static string Compile(string request) => Service.CompileJson(request);

    [JSExport]
    public static string Run(string request) => Service.RunJson(request, ExecutionStarted);

    [JSExport]
    public static string Test(string request) => Service.TestJson(request, ExecutionStarted);

    /// <summary>Первая компиляция в WebAssembly долгая: прогреваем Roslyn до первого запроса ученика.</summary>
    [JSExport]
    public static void Warmup() => Service.Run(new RunRequest("System.Console.Write(1);"));

    /// <summary>Сообщает фронту, что код ученика начал выполняться: с этого момента идёт таймаут.</summary>
    [JSImport("executionStarted", "runner")]
    private static partial void ExecutionStarted();
}
