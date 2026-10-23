using System.Text.RegularExpressions;
using CSharpSchool.Runner;
using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Content.Tests;

/// <summary>Формат курса (content/README.md) и правила учебного контента (rules/course-content.md)</summary>
public sealed partial class StructureTests
{
    private const string Russian = "ru";

    [Fact]
    public void EveryFileReadsWithoutUnknownFields()
    {
        // Чтение всех файлов само падает на опечатке в ключе или пропущенном поле
        Assert.NotEmpty(Course.Lessons);
        Assert.All(Course.TaskPaths, path => Assert.NotNull(Course.Task(path)));
    }

    [Fact]
    public void EveryTextIsInRussianAndReferencedFilesExist()
    {
        var texts = new List<(string Where, Dictionary<string, string> Text)> { ("course.json", Course.File.Title), ("course.json", Course.File.Description) };

        foreach (var (path, module) in Course.Modules)
        {
            texts.Add((path, module.Title));
            texts.Add((path, module.Description));
        }

        foreach (var (path, lesson) in Course.Lessons)
        {
            texts.Add((path, lesson.Title));
            foreach (var theory in lesson.Steps.OfType<TheoryStep>())
            {
                texts.Add((path, theory.Text));
                Assert.All(theory.Text.Values, file => Assert.True(File.Exists(Path.Combine(ContentFiles.Root, path, file)), $"{path}: нет {file}"));
            }

            foreach (var question in lesson.Steps.OfType<QuizStep>().SelectMany(quiz => quiz.Questions))
            {
                texts.AddRange([(path, question.Question), (path, question.Explanation), .. question.Options.Select(option => (path, option))]);
            }
        }

        foreach (var path in Course.TaskPaths)
        {
            var task = Course.Task(path);
            texts.AddRange([(path, task.Title), (path, task.Statement), .. task.Messages.Select(m => (path, m)), .. task.Hints.Select(h => (path, h))]);
            Assert.All(
                [task.Starter, task.Solution, .. task.Statement.Values, .. task.Wrong.Select(w => w.File)],
                file => Assert.True(File.Exists(Path.Combine(ContentFiles.Root, path, file)), $"{path}: нет {file}"));
        }

        Assert.All(texts, text => Assert.False(string.IsNullOrWhiteSpace(text.Text.GetValueOrDefault(Russian)), $"{text.Where}: нет русского текста"));
    }

    [Fact]
    public void TasksFollowCourseRules()
    {
        Assert.All(Course.TaskPaths, path =>
        {
            var task = Course.Task(path);

            Assert.True(task.Hints.Count == 3, $"{path}: подсказок должно быть три (намёк → подробнее → почти решение)");
            Assert.True(task.Messages.Count == task.Checks.Count, $"{path}: у каждой проверки — своё сообщение о провале");
            Assert.True(task.Wrong.Count > 0, $"{path}: нет ни одного неверного решения");
            Assert.All(task.Wrong, wrong => Assert.True(
                (wrong.Fails is { Count: > 0 }) ^ (wrong.CompileError is not null),
                $"{path}/{wrong.File}: нужно либо fails, либо compileError"));
            Assert.All(task.Wrong.SelectMany(w => w.Fails ?? []), index => Assert.InRange(index, 0, task.Checks.Count - 1));
        });
    }

    [Fact]
    public void EveryTaskFolderIsUsedOnce()
    {
        var used = Course.TaskPaths.ToList();
        var folders = Course.Lessons
            .SelectMany(lesson => Directory.Exists(Path.Combine(ContentFiles.Root, lesson.Path, "tasks"))
                ? Directory.GetDirectories(Path.Combine(ContentFiles.Root, lesson.Path, "tasks")).Select(d => Path.GetRelativePath(ContentFiles.Root, d))
                : [])
            .ToList();

        Assert.Equal(used.Count, used.Distinct().Count());
        Assert.Equal(folders.Order(), used.Order());
    }

    [Fact]
    public void EveryModuleEndsWithItsOnlyTest()
    {
        // Модуль считается пройденным только после контрольной (rules/course-content.md): она одна и последняя
        foreach (var (path, module) in Course.Modules)
        {
            Assert.NotEmpty(module.Lessons);
            var kinds = module.Lessons.Select(slug => ContentFiles.Read<LessonFile>(path, slug, "lesson.json").Kind).ToList();
            Assert.All(kinds, kind => Assert.Contains(kind, new[] { LessonKinds.Lesson, LessonKinds.Test }));
            Assert.True(kinds[^1] == LessonKinds.Test, $"{path}: последний урок модуля — контрольная: \"kind\": \"test\" в lesson.json");
            Assert.True(kinds.Count(kind => kind == LessonKinds.Test) == 1, $"{path}: контрольная в модуле одна — последний урок");
        }
    }

    [Fact]
    public void QuizzesHaveTwoToFourQuestionsWithValidAnswers()
    {
        foreach (var (path, lesson) in Course.Lessons)
        {
            var quizzes = lesson.Steps.OfType<QuizStep>().ToList();
            Assert.True(quizzes.Count == 1 && lesson.Steps[^1] is QuizStep, $"{path}: урок заканчивается одной проверкой знаний");

            var questions = quizzes[0].Questions;
            Assert.InRange(questions.Count, 2, 4);
            Assert.All(questions, q => Assert.InRange(q.Answer, 0, q.Options.Count - 1));
        }
    }

    [Fact]
    public void CodeInTheoryAndStatementsCompiles()
    {
        var runner = new RunnerService();
        var files = Course.Lessons
            .SelectMany(lesson => lesson.Lesson.Steps.OfType<TheoryStep>().SelectMany(s => s.Text.Values).Select(file => Path.Combine(lesson.Path, file)))
            .Concat(Course.TaskPaths.SelectMany(path => Course.Task(path).Statement.Values.Select(file => Path.Combine(path, file))));

        foreach (var file in files)
        {
            foreach (Match block in CSharpBlock().Matches(Course.Text(file)))
            {
                var response = runner.Compile(new CompileRequest(block.Groups[1].Value));
                Assert.True(response.Compiled, $"{file}: код из текста не компилируется:\n{block.Groups[1].Value}\n{string.Join("\n", response.Diagnostics.Select(d => $"{d.Code}: {d.Message}"))}");
            }
        }
    }

    [GeneratedRegex(@"```csharp\n(.*?)```", RegexOptions.Singleline)]
    private static partial Regex CSharpBlock();
}
