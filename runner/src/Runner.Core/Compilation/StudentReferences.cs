using System.Reflection;
using Microsoft.CodeAnalysis;

namespace CSharpSchool.Runner.Compilation;

/// <summary>
/// Справочник .NET, против которого компилируется код ученика: сборки встроены в эту библиотеку
/// (список — в Runner.Core.csproj, StudentReference). Читаются один раз.
/// </summary>
internal static class StudentReferences
{
    private const string Prefix = "refs/";

    private static readonly Lazy<IReadOnlyList<MetadataReference>> Cache = new(Load);

    public static IReadOnlyList<MetadataReference> All => Cache.Value;

    private static List<MetadataReference> Load()
    {
        var assembly = typeof(StudentReferences).Assembly;

        return [.. assembly.GetManifestResourceNames()
            .Where(name => name.StartsWith(Prefix, StringComparison.Ordinal))
            .Order(StringComparer.Ordinal)
            .Select(name => Read(assembly, name))];
    }

    private static PortableExecutableReference Read(Assembly assembly, string name)
    {
        using var stream = assembly.GetManifestResourceStream(name)
            ?? throw new InvalidOperationException($"Нет встроенной сборки {name}");

        return MetadataReference.CreateFromStream(stream, filePath: name[Prefix.Length..]);
    }
}
