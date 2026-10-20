namespace CSharpSchool.Content.Tests;

/// <summary>Весь курс: модули, уроки и задачи с путями к их папкам</summary>
public static class Course
{
    public static CourseFile File { get; } = ContentFiles.Read<CourseFile>("course.json");

    public static IEnumerable<(string Path, ModuleFile Module)> Modules =>
        File.Modules.Select(slug => (slug, ContentFiles.Read<ModuleFile>(slug, "module.json")));

    public static IEnumerable<(string Path, LessonFile Lesson)> Lessons =>
        Modules.SelectMany(module => module.Module.Lessons.Select(slug =>
        {
            var path = Path.Combine(module.Path, slug);
            return (path, ContentFiles.Read<LessonFile>(path, "lesson.json"));
        }));

    /// <summary>Папки задач относительно content: «01-first-programs/01-hello/tasks/hello-world»</summary>
    public static IEnumerable<string> TaskPaths =>
        Lessons.SelectMany(lesson => lesson.Lesson.Steps.OfType<TaskStep>().Select(step => Path.Combine(lesson.Path, "tasks", step.Task)));

    public static TaskFile Task(string path) => ContentFiles.Read<TaskFile>(path, "task.json");

    public static string Text(params string[] path) => System.IO.File.ReadAllText(Path.Combine([ContentFiles.Root, .. path]));

    public static TheoryData<string> Tasks => [.. TaskPaths];

    public static TheoryData<string, string> WrongSolutions =>
        [.. TaskPaths.SelectMany(path => Task(path).Wrong.Select(wrong => (path, wrong.File)))];
}
