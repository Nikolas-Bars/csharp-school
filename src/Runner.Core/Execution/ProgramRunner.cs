using System.Collections.Immutable;
using System.Diagnostics;
using System.Diagnostics.CodeAnalysis;
using System.Reflection;
using System.Reflection.Metadata;
using System.Reflection.Metadata.Ecma335;
using CSharpSchool.Runner.Compilation;
using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner.Execution;

/// <summary>
/// Запускает скомпилированную программу ученика: подменяет Console на свой ввод и вывод, ловит
/// исключение и находит строку, где оно произошло. Остановить зависшую программу отсюда нельзя —
/// в браузере это делает фронт, перезапуская Web Worker по таймауту.
/// </summary>
public sealed class ProgramRunner(int outputLimit = ProgramRunner.DefaultOutputLimit)
{
    /// <summary>Сколько символов программа может напечатать.</summary>
    public const int DefaultOutputLimit = 100_000;

    /// <summary>
    /// Console — один на весь процесс: две программы одновременно перепутали бы вывод.
    /// В браузере поток и так один, а в тестах и проверках курса на обычном .NET — нет.
    /// </summary>
    private static readonly Lock ConsoleLock = new();

    /// <param name="onStart">Вызывается прямо перед запуском кода ученика: с этого момента фронт считает таймаут.</param>
    public ProgramResult Run(StudentProgram program, string? stdin, Action? onStart = null)
    {
        if (program.Assembly is null)
        {
            throw new InvalidOperationException("Программа не скомпилирована");
        }

        var assembly = LoadStudentAssembly(program);
        var main = EntryPoint(assembly.EntryPoint ?? throw new InvalidOperationException("У программы нет точки входа"));

        var output = new LimitedWriter(outputLimit);
        RuntimeError? error = null;

        using var scope = ConsoleLock.EnterScope();
        // Console.In не читаем: в браузере его геттер бросает PlatformNotSupportedException
        var (previousOut, previousError) = (Console.Out, Console.Error);

        Console.SetOut(output);
        Console.SetError(output);
        Console.SetIn(new StringReader(stdin ?? string.Empty));

        try
        {
            onStart?.Invoke();
            var result = main();

            // async Main (await в коде верхнего уровня)
            if (result is Task task)
            {
                task.GetAwaiter().GetResult();
            }
        }
        catch (OutputLimitExceededException)
        {
            // Ученику сообщается флагом OutputLimitExceeded, это не ошибка программы
        }
        catch (Exception exception)
        {
            error = new RuntimeError(exception.GetType().Name, exception.Message, StudentLine(exception, assembly, program.Symbols));
        }
        finally
        {
            Console.SetOut(previousOut);
            Console.SetError(previousError);
            // Ввод этого запуска не должен достаться следующему
            Console.SetIn(TextReader.Null);
        }

        return new ProgramResult(output.ToString(), output.LimitExceeded, error);
    }

    /// <summary>
    /// Точка входа как делегат. Не MethodInfo.Invoke: в браузере исключение, прошедшее через
    /// рефлексию, теряет кадры кода ученика — и строку, где программа упала. Main в C# бывает
    /// только таких видов: без аргументов или со string[] args, возвращает void, int, Task или Task&lt;int&gt;.
    /// </summary>
    private static Func<object?> EntryPoint(MethodInfo method)
    {
        string[] args = [];
        var withArgs = method.GetParameters().Length == 1;

        if (method.ReturnType == typeof(void))
        {
            if (withArgs)
            {
                var action = method.CreateDelegate<Action<string[]>>();
                return () => { action(args); return null; };
            }

            var plain = method.CreateDelegate<Action>();
            return () => { plain(); return null; };
        }

        return method.ReturnType == typeof(int) ? Call<int>(method, withArgs, args)
            : method.ReturnType == typeof(Task) ? Call<Task>(method, withArgs, args)
            : Call<Task<int>>(method, withArgs, args);
    }

    private static Func<object?> Call<TResult>(MethodInfo method, bool withArgs, string[] args)
    {
        if (withArgs)
        {
            var function = method.CreateDelegate<Func<string[], TResult>>();
            return () => function(args);
        }

        var plain = method.CreateDelegate<Func<TResult>>();
        return () => plain();
    }

    [UnconditionalSuppressMessage(
        "Trimming",
        "IL2026",
        Justification = "Код ученика компилируется только против StudentReferences, а при сборке под браузер эти сборки не обрезаются (Runner.Browser.csproj).")]
    private static Assembly LoadStudentAssembly(StudentProgram program) => Assembly.Load(program.Assembly!);

    /// <summary>
    /// Строка кода ученика, где произошло исключение. Берётся из отладочной информации (PDB)
    /// самостоятельно: по смещению инструкции в кадре стека ищется точка «инструкция → строка».
    /// Runtime в браузере PDB из памяти не подключает, так одинаково работает везде.
    /// </summary>
    private static int? StudentLine(Exception exception, Assembly student, byte[]? symbols)
    {
        if (symbols is null)
        {
            return null;
        }

        using var provider = MetadataReaderProvider.FromPortablePdbImage(ImmutableArray.Create(symbols));
        var pdb = provider.GetMetadataReader();

        foreach (var frame in new StackTrace(exception).GetFrames())
        {
            var method = StudentMethod(frame, student);
            var offset = frame.GetILOffset();

            if (method is null || offset == StackFrame.OFFSET_UNKNOWN)
            {
                continue;
            }

            var row = MetadataTokens.GetRowNumber(MetadataTokens.EntityHandle(method.MetadataToken));
            var point = pdb.GetMethodDebugInformation(MetadataTokens.MethodDebugInformationHandle(row))
                .GetSequencePoints()
                .LastOrDefault(point => !point.IsHidden && point.Offset <= offset);

            if (point.StartLine > 0)
            {
                return point.StartLine;
            }
        }

        return null;
    }

    [UnconditionalSuppressMessage(
        "Trimming",
        "IL2026",
        Justification = "Нужны только кадры сборки ученика: её загружают во время работы, обрезка её не трогает, метаданные полные.")]
    private static MethodBase? StudentMethod(StackFrame frame, Assembly student) =>
        frame.GetMethod() is { } method && method.Module.Assembly == student ? method : null;
}

public sealed record ProgramResult(string Output, bool OutputLimitExceeded, RuntimeError? Error);
