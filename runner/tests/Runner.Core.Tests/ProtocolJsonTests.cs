namespace CSharpSchool.Runner.Tests;

/// <summary>JSON протокола — как его отправляет и читает фронт (src/runner/protocol.ts).</summary>
public sealed class ProtocolJsonTests
{
    private static readonly RunnerService Service = new();

    [Fact]
    public void ReadsTestRequestFromFrontend()
    {
        const string request = """
            {
              "code": "System.Console.Write(\"Жив\");",
              "checks": [
                { "kind": "output", "expected": "Жив", "match": "contains" },
                { "kind": "construct", "construct": "doWhile" },
                { "kind": "call", "method": "System.Console.Write" }
              ]
            }
            """;

        var response = Service.TestJson(request);

        Assert.Contains("\"compiled\":true", response, StringComparison.Ordinal);
        Assert.Contains("\"passed\":false", response, StringComparison.Ordinal);
        Assert.Contains("\"results\":[{\"index\":0,\"passed\":true", response, StringComparison.Ordinal);
    }

    [Fact]
    public void ReadsNewChecksAndWritesSetError()
    {
        const string request = """
            {
              "code": "int height = 3;\nfor (int i = 0; i < height; i++) { if (i == 1) { continue; } break; }\nvoid Report() { System.Console.Write(height); }\nReport();",
              "checks": [
                { "kind": "output", "expected": "5", "set": { "height": 5 } },
                { "kind": "output", "expected": "5", "set": { "width": 5 } },
                { "kind": "construct", "construct": "break" },
                { "kind": "construct", "construct": "continue" },
                { "kind": "call", "method": "System.Console.Write", "caller": "Program.Report" }
              ]
            }
            """;

        var response = Service.TestJson(request);

        Assert.Contains("\"passed\":false", response, StringComparison.Ordinal);
        Assert.Contains("{\"index\":0,\"passed\":true", response, StringComparison.Ordinal);
        Assert.Contains("\"setError\":{\"variable\":\"width\",\"problem\":\"notFound\",\"type\":null}", response, StringComparison.Ordinal);
        Assert.Contains("{\"index\":2,\"passed\":true", response, StringComparison.Ordinal);
        Assert.Contains("{\"index\":3,\"passed\":true", response, StringComparison.Ordinal);
        Assert.Contains("{\"index\":4,\"passed\":true", response, StringComparison.Ordinal);
        Assert.Contains("\"setError\":null", response, StringComparison.Ordinal);
    }

    [Fact]
    public void WritesCamelCaseWithLowercaseEnumsAndExplicitNulls()
    {
        var response = Service.RunJson("""{ "code": "int unused = 1;" }""");

        Assert.Contains("\"severity\":\"warning\"", response, StringComparison.Ordinal);
        Assert.Contains("\"error\":null", response, StringComparison.Ordinal);
        Assert.Contains("\"arguments\":[\"unused\"]", response, StringComparison.Ordinal);
    }
}
