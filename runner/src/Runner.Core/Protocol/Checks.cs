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
[JsonDerivedType(typeof(AccessCheck), "access")]
public abstract record Check;

/// <summary>Программа с таким вводом печатает такой текст. Переводы строк \r\n и \n равны, хвостовые пробелы и пустые строки в конце не важны.</summary>
/// <param name="Set">
/// Запустить программу с другими начальными значениями: «имя переменной → значение» (JSON-число,
/// строка, true/false или массив из них — для <c>int[]</c> и <c>List&lt;int&gt;</c>). Подменяется значение
/// при объявлении переменной в основном коде: <c>int height = 4;</c> → <c>int height = 5;</c>,
/// <c>int[] scores = { 1, 2 };</c> → <c>new int[] { 5, 9, 1 }</c>. Так пустой цикл и готовый ответ в Console.WriteLine
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

    /// <summary>Объявление конструктора класса: <c>public Hero(int hp) { … }</c>.</summary>
    Constructor,

    /// <summary>Объявление свойства: <c>public int Hp { get; private set; }</c>.</summary>
    Property,

    /// <summary>Метод доступа set с private: <c>{ get; private set; }</c>.</summary>
    PrivateSet,

    /// <summary>Слово this: <c>this.hp = hp;</c>.</summary>
    This,

    /// <summary>Объявление перечисления: <c>enum State { Idle, Walk, Attack }</c>.</summary>
    Enum,

    /// <summary>Класс с базовым классом: <c>class Orc : Enemy</c>. <c>min</c> — сколько таких классов-наследников.</summary>
    Inheritance,

    /// <summary>Метод с virtual: <c>public virtual void Attack()</c>.</summary>
    Virtual,

    /// <summary>Метод с override: <c>public override void Attack()</c> (override ToString тоже считается).</summary>
    Override,

    /// <summary>Член класса с protected — поле, метод, свойство, конструктор — или метод доступа свойства: <c>protected int hp;</c>, <c>{ get; protected set; }</c>.</summary>
    Protected,

    /// <summary>Вызов конструктора базового класса у конструктора: <c>public Orc(string name) : base(name, 60)</c>.</summary>
    BaseConstructor,

    /// <summary>break, который выходит из цикла; break в switch не считается.</summary>
    Break,
    Continue,
}

/// <summary>В коде вызывается метод: полное имя, например «System.Console.WriteLine», «System.Math.Max» или свой «Program.Report».</summary>
/// <param name="Caller">
/// Если задан — вызов засчитывается, только когда он в теле этого метода (то же полное имя: «Program.Report»,
/// метод класса — «Hero.Heal»), конструктора («Hero.Hero» — имя класса дважды) или свойства («Hero.Hp» — в get или set).
/// </param>
public sealed record CallCheck(string Method, string? Caller = null) : Check;

/// <summary>
/// Значение поля или свойства читается в коде не меньше <paramref name="Min"/> раз: «Hero.hp» — поле hp класса Hero.
/// Запись (<c>hero.hp = 5</c>, <c>+=</c>, <c>++</c>) не считается. Так видно, что печатается поле объекта,
/// а не исходная переменная: <c>Console.WriteLine(hero.hp)</c>, а не <c>Console.WriteLine(hp)</c>.
/// </summary>
public sealed record AccessCheck(string Member, int Min = 1) : Check;
