using System.Globalization;
using System.Text;
using System.Text.RegularExpressions;
using CSharpSchool.Runner.Protocol;
using Microsoft.CodeAnalysis;

namespace CSharpSchool.Runner.Compilation;

/// <summary>Ошибка Roslyn → DiagnosticInfo протокола: код, позиция с 1, оригинал и аргументы.</summary>
internal static partial class DiagnosticConverter
{
    public static DiagnosticInfo Convert(Diagnostic diagnostic)
    {
        var span = diagnostic.Location.GetLineSpan();
        var message = diagnostic.GetMessage(CultureInfo.InvariantCulture);

        return new DiagnosticInfo(
            diagnostic.Id,
            diagnostic.Severity == DiagnosticSeverity.Error ? DiagnosticLevel.Error : DiagnosticLevel.Warning,
            message,
            Arguments(diagnostic, message),
            span.StartLinePosition.Line + 1,
            span.StartLinePosition.Character + 1,
            span.EndLinePosition.Line + 1,
            span.EndLinePosition.Character + 1);
    }

    /// <summary>
    /// Значения, подставленные в шаблон сообщения: для «The name 'hp' does not exist…» — «hp».
    /// Публичного API для них у Roslyn нет, поэтому шаблон («The name '{0}' does not exist…»)
    /// превращается в регулярное выражение и сопоставляется с готовым сообщением.
    /// </summary>
    private static List<string> Arguments(Diagnostic diagnostic, string message)
    {
        var format = diagnostic.Descriptor.MessageFormat.ToString(CultureInfo.InvariantCulture);
        var placeholders = Placeholder().Matches(format);

        if (placeholders.Count == 0)
        {
            return [];
        }

        var pattern = new StringBuilder("^");
        var seen = new SortedSet<int>();
        var position = 0;

        foreach (Match placeholder in placeholders)
        {
            pattern.Append(Regex.Escape(format[position..placeholder.Index]));
            var index = int.Parse(placeholder.Groups[1].ValueSpan, CultureInfo.InvariantCulture);
            pattern.Append(seen.Add(index) ? $"(?<a{index}>.*?)" : $@"\k<a{index}>");
            position = placeholder.Index + placeholder.Length;
        }

        pattern.Append(Regex.Escape(format[position..])).Append('$');

        var match = Regex.Match(message, pattern.ToString(), RegexOptions.Singleline, TimeSpan.FromMilliseconds(100));

        return match.Success
            ? [.. Enumerable.Range(0, seen.Max + 1).Select(index => match.Groups[$"a{index}"].Value)]
            : [];
    }

    [GeneratedRegex(@"\{(\d+)\}")]
    private static partial Regex Placeholder();
}
