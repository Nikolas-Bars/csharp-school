using System.Text;
using CSharpSchool.Runner.Protocol;
using Microsoft.CodeAnalysis;
using Microsoft.CodeAnalysis.CSharp;
using Microsoft.CodeAnalysis.Emit;
using Microsoft.CodeAnalysis.Text;

namespace CSharpSchool.Runner.Compilation;

/// <summary>
/// Компилирует код ученика в сборку в памяти. Настройки — как в Unity, куда ведёт курс:
/// C# 9 и без неявных using (using System; пишется явно, как в скриптах Unity).
/// </summary>
public sealed class StudentCompiler
{
    private static readonly CSharpParseOptions ParseOptions = new(LanguageVersion.CSharp9);

    private static readonly CSharpCompilationOptions CompilationOptions = new(
        OutputKind.ConsoleApplication,
        optimizationLevel: OptimizationLevel.Debug,
        // В браузере у .NET один поток
        concurrentBuild: false,
        nullableContextOptions: NullableContextOptions.Disable);

    private int compiledCount;

    /// <param name="emit">false — только найти ошибки (для подсветки в редакторе), без сборки программы.</param>
    public StudentProgram Compile(string code, bool emit = true)
    {
        // Кодировка нужна для отладочной информации: по ней находятся строки исключений.
        // Пути у файла нет намеренно: для файла с путём Roslyn считает контрольную сумму через
        // Task.Run и ждёт её, а в однопоточном WebAssembly фоновая задача не выполнится, пока
        // поток ждёт, — в Web Worker это вечное зависание. Документ без пути создаётся без суммы
        var tree = CSharpSyntaxTree.ParseText(SourceText.From(code, Encoding.UTF8), ParseOptions);
        // У каждой сборки своё имя: в одном процессе их загружается много
        var name = $"StudentProgram{Interlocked.Increment(ref compiledCount)}";
        var compilation = CSharpCompilation.Create(name, [tree], StudentReferences.All, CompilationOptions);

        var diagnostics = compilation.GetDiagnostics()
            .Where(diagnostic => diagnostic.Severity is DiagnosticSeverity.Error or DiagnosticSeverity.Warning)
            .OrderBy(diagnostic => diagnostic.Location.SourceSpan.Start)
            .ThenBy(diagnostic => diagnostic.Id, StringComparer.Ordinal)
            .Select(DiagnosticConverter.Convert)
            .ToList();

        if (!emit || diagnostics.Any(diagnostic => diagnostic.Severity == DiagnosticLevel.Error))
        {
            return new StudentProgram(compilation, tree, diagnostics, null, null);
        }

        using var pe = new MemoryStream();
        using var pdb = new MemoryStream();
        var result = compilation.Emit(pe, pdb, options: new EmitOptions(debugInformationFormat: DebugInformationFormat.PortablePdb));

        if (!result.Success)
        {
            // Ошибки, которые видны только при сборке (их почти не бывает)
            diagnostics.AddRange(result.Diagnostics
                .Where(diagnostic => diagnostic.Severity == DiagnosticSeverity.Error)
                .Select(DiagnosticConverter.Convert));

            return new StudentProgram(compilation, tree, diagnostics, null, null);
        }

        return new StudentProgram(compilation, tree, diagnostics, pe.ToArray(), pdb.ToArray());
    }
}

/// <summary>Результат компиляции: ошибки и, если их нет, сборка с отладочной информацией (номера строк).</summary>
public sealed record StudentProgram(
    CSharpCompilation Compilation,
    SyntaxTree Tree,
    IReadOnlyList<DiagnosticInfo> Diagnostics,
    byte[]? Assembly,
    byte[]? Symbols)
{
    public bool Compiled => Assembly is not null;
}
