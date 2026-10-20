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
    public void WritesCamelCaseWithLowercaseEnumsAndExplicitNulls()
    {
        var response = Service.RunJson("""{ "code": "int unused = 1;" }""");

        Assert.Contains("\"severity\":\"warning\"", response, StringComparison.Ordinal);
        Assert.Contains("\"error\":null", response, StringComparison.Ordinal);
        Assert.Contains("\"arguments\":[\"unused\"]", response, StringComparison.Ordinal);
    }
}
