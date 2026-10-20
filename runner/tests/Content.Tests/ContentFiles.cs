using System.Text.Json;
using System.Text.Json.Serialization;
using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Content.Tests;

/// <summary>
/// Файлы курса так, как их описывает content/README.md. Лишнее поле или нет обязательного —
/// ошибка чтения: опечатка в ключе не должна молча пропасть.
/// </summary>
public static class ContentFiles
{
    private static readonly JsonSerializerOptions Options = new(JsonSerializerDefaults.Web)
    {
        UnmappedMemberHandling = JsonUnmappedMemberHandling.Disallow,
        RespectRequiredConstructorParameters = true,
        RespectNullableAnnotations = true,
    };

    /// <summary>Папка content: ищется вверх от тестов (в Docker и в CI репозиторий целиком)</summary>
    public static string Root { get; } = FindRoot();

    public static T Read<T>(params string[] path) =>
        JsonSerializer.Deserialize<T>(File.ReadAllText(Path.Combine([Root, .. path])), Options)
        ?? throw new InvalidDataException(Path.Combine(path));

    private static string FindRoot()
    {
        for (var directory = new DirectoryInfo(AppContext.BaseDirectory); directory is not null; directory = directory.Parent)
        {
            var content = Path.Combine(directory.FullName, "content");
            if (File.Exists(Path.Combine(content, "course.json")))
            {
                return content;
            }
        }

        throw new DirectoryNotFoundException("Не найдена папка content с course.json выше " + AppContext.BaseDirectory);
    }
}

public sealed record CourseFile(string Slug, Dictionary<string, string> Title, List<string> Modules);

public sealed record ModuleFile(Dictionary<string, string> Title, Dictionary<string, string> Description, List<string> Lessons);

public sealed record LessonFile(Dictionary<string, string> Title, List<StepFile> Steps);

[JsonPolymorphic(TypeDiscriminatorPropertyName = "type")]
[JsonDerivedType(typeof(TheoryStep), "theory")]
[JsonDerivedType(typeof(TaskStep), "task")]
[JsonDerivedType(typeof(QuizStep), "quiz")]
public abstract record StepFile;

public sealed record TheoryStep(Dictionary<string, string> Text) : StepFile;

public sealed record TaskStep(string Task) : StepFile;

public sealed record QuizStep(List<QuizQuestion> Questions) : StepFile;

public sealed record QuizQuestion(
    Dictionary<string, string> Question,
    List<Dictionary<string, string>> Options,
    int Answer,
    Dictionary<string, string> Explanation);

public sealed record TaskFile(
    Dictionary<string, string> Title,
    Dictionary<string, string> Statement,
    string Starter,
    List<Check> Checks,
    List<Dictionary<string, string>> Messages,
    List<Dictionary<string, string>> Hints,
    string Solution,
    List<WrongSolution> Wrong);

/// <param name="Fails">Номера проверок, которые должны упасть — ровно эти.</param>
/// <param name="CompileError">Или код ошибки компилятора, с которой решение не собирается.</param>
public sealed record WrongSolution(string File, string Why, List<int>? Fails = null, string? CompileError = null);
