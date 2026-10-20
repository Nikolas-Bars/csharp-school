using System.Collections.Immutable;
using System.Reflection.Metadata;
using CSharpSchool.Runner.Compilation;

namespace CSharpSchool.Runner.Tests;

/// <summary>
/// То, что ломается только в браузере (однопоточный WebAssembly), а на обычном .NET работает.
/// Сам браузер в этих тестах не запускается — проверяется условие, без которого он зависает.
/// </summary>
public sealed class BrowserSafetyTests
{
    [Fact]
    public void DebugInfoHasNoSourceChecksum()
    {
        // Контрольную сумму файла Roslyn считает через Task.Run и ждёт результата. В однопоточном
        // WebAssembly это вечное зависание Web Worker. Суммы нет, когда у файла нет пути
        // (StudentCompiler) — здесь проверяется, что это так и осталось.
        var program = new StudentCompiler().Compile("System.Console.Write(1);");

        using var provider = MetadataReaderProvider.FromPortablePdbImage(ImmutableArray.Create(program.Symbols!));
        var pdb = provider.GetMetadataReader();

        Assert.NotEmpty(pdb.Documents);
        Assert.All(pdb.Documents, handle => Assert.True(pdb.GetDocument(handle).Hash.IsNil));
    }
}
