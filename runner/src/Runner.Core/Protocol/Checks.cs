using System.Text.Json;
using System.Text.Json.Serialization;

namespace CSharpSchool.Runner.Protocol;

/// <summary>
/// Проверка решения задачи. Какой текст показать ученику при провале — решает задача
/// (учебный контент), runner сообщает только факт и что ожидалось / что получилось.
/// </summary>
[JsonPolymorphic(TypeDiscriminatorPropertyName = "kind")]
[JsonDerivedType(typeof(OutputCheck), "output")]
[JsonDerivedType(typeof(VariableCheck), "variable")]
[JsonDerivedType(typeof(ConstructCheck), "construct")]
[JsonDerivedType(typeof(CallCheck), "call")]
public abstract record Check;

/// <summary>Программа с таким вводом печатает такой текст. Переводы строк \r\n и \n равны, хвостовые пробелы и пустые строки в конце не важны.</summary>
/// <param name="Set">
/// Запустить программу с другими начальными значениями: «имя переменной → значение» (JSON-число,
/// строка или true/false). Подменяется значение при объявлении переменной в основном коде:
/// <c>int height = 4;</c> → <c>int height = 5;</c>. Так пустой цикл и готовый ответ в Console.WriteLine
/// не проходят проверку. Код ученика при обычном запуске не меняется. Подробно — InitialValues.
/// </param>
public sealed record OutputCheck(
    string Expected,
    string? Stdin = null,
    OutputMatch Match = OutputMatch.Exact,
    IReadOnlyDictionary<string, JsonElement>? Set = null) : Check;

[JsonConverter(typeof(CamelCaseEnumConverter<OutputMatch>))]
public enum OutputMatch
{
    /// <summary>Вывод целиком равен ожидаемому.</summary>
    Exact,

    /// <summary>Ожидаемый текст есть где-то в выводе.</summary>
    Contains,
}

/// <summary>В коде объявлена переменная с таким именем (регистр важен: hp и HP — разные) и, если задан, такого типа: «int», «string», «List&lt;int&gt;».</summary>
public sealed record VariableCheck(string Name, string? Type = null) : Check;

/// <summary>Конструкция встречается в коде не меньше <paramref name="Min"/> раз.</summary>
public sealed record ConstructCheck(Construct Construct, int Min = 1) : Check;

[JsonConverter(typeof(CamelCaseEnumConverter<Construct>))]
public enum Construct
{
    If,
    Else,
    For,
    Foreach,
    While,
    DoWhile,
    Switch,
    Method,
    Class,

    /// <summary>break, который выходит из цикла; break в switch не считается.</summary>
    Break,
    Continue,
}

/// <summary>В коде вызывается метод: полное имя, например «System.Console.WriteLine», «System.Math.Max» или свой «Program.Report».</summary>
/// <param name="Caller">Если задан — вызов засчитывается, только когда он в теле этого метода (то же полное имя: «Program.Report»).</param>
public sealed record CallCheck(string Method, string? Caller = null) : Check;
