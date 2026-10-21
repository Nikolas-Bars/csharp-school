using System.Text.Json.Serialization;

namespace CSharpSchool.Runner.Protocol;

public sealed record CompileResponse(bool Compiled, IReadOnlyList<DiagnosticInfo> Diagnostics);

/// <param name="Output">Всё, что программа напечатала (и в Console.Out, и в Console.Error).</param>
/// <param name="OutputLimitExceeded">Программа напечатала слишком много и была остановлена.</param>
/// <param name="Error">Исключение, на котором программа упала; null — завершилась нормально.</param>
public sealed record RunResponse(
    bool Compiled,
    IReadOnlyList<DiagnosticInfo> Diagnostics,
    string Output,
    bool OutputLimitExceeded,
    RuntimeError? Error);

public sealed record TestResponse(
    bool Compiled,
    IReadOnlyList<DiagnosticInfo> Diagnostics,
    bool Passed,
    IReadOnlyList<CheckResult> Results);

/// <param name="Index">Номер проверки в запросе.</param>
/// <param name="Expected">Что ожидалось (для вывода — текст, для конструкции — минимум раз).</param>
/// <param name="Actual">Что получилось: вывод программы, найденный тип переменной, число вызовов…</param>
/// <param name="Error">Для проверки вывода — исключение, на котором программа упала.</param>
/// <param name="SetError">Для проверки вывода с <c>set</c> — почему не получилось подменить значение (программа тогда не запускается).</param>
public sealed record CheckResult(
    int Index,
    bool Passed,
    string? Expected = null,
    string? Actual = null,
    bool OutputLimitExceeded = false,
    RuntimeError? Error = null,
    SetError? SetError = null);

/// <param name="Variable">Имя переменной из <c>set</c> (для NotCompiled — все имена через запятую).</param>
/// <param name="Type">Для WrongType — тип переменной в коде ученика: «int», «Random».</param>
public sealed record SetError(string Variable, SetProblem Problem, string? Type = null);

[JsonConverter(typeof(CamelCaseEnumConverter<SetProblem>))]
public enum SetProblem
{
    /// <summary>В основном коде (не в цикле, не в методе) нет объявления переменной с таким именем.</summary>
    NotFound,

    /// <summary>Переменная объявлена без начального значения: <c>int height;</c>.</summary>
    NoValue,

    /// <summary>Значение не подходит к типу переменной (строка для int, дробное для int, число для массива, тип не простой).</summary>
    WrongType,

    /// <summary>Значения подставились, но код с ними не собирается (например, длина массива из const). Variable — все имена из set через запятую.</summary>
    NotCompiled,
}

/// <summary>
/// Ошибка или предупреждение компилятора. Позиции с 1, как в редакторе. Message — оригинал
/// на английском; понятный русский текст фронт берёт по Code, подставляя Arguments.
/// </summary>
public sealed record DiagnosticInfo(
    string Code,
    DiagnosticLevel Severity,
    string Message,
    IReadOnlyList<string> Arguments,
    int Line,
    int Column,
    int EndLine,
    int EndColumn);

[JsonConverter(typeof(CamelCaseEnumConverter<DiagnosticLevel>))]
public enum DiagnosticLevel
{
    Error,
    Warning,
}

/// <param name="Type">Короткое имя исключения: «DivideByZeroException».</param>
/// <param name="Line">Строка кода ученика, где оно произошло, если известна.</param>
public sealed record RuntimeError(string Type, string Message, int? Line);
