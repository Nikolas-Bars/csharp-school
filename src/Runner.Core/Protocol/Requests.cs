namespace CSharpSchool.Runner.Protocol;

/// <summary>Только скомпилировать: ошибки для подсветки в редакторе.</summary>
public sealed record CompileRequest(string Code);

/// <summary>Скомпилировать и запустить; <paramref name="Stdin"/> — то, что программа прочитает через Console.ReadLine.</summary>
public sealed record RunRequest(string Code, string? Stdin = null);

/// <summary>Скомпилировать и проверить решение задачи.</summary>
public sealed record TestRequest(string Code, IReadOnlyList<Check> Checks);
