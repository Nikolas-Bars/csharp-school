using System.Text.Json;
using System.Text.Json.Serialization;

namespace CSharpSchool.Runner.Protocol;

/// <summary>JSON протокола — camelCase, сериализация сгенерирована заранее (без рефлексии).</summary>
[JsonSourceGenerationOptions(
    JsonSerializerDefaults.Web,
    DefaultIgnoreCondition = JsonIgnoreCondition.Never)]
[JsonSerializable(typeof(CompileRequest))]
[JsonSerializable(typeof(RunRequest))]
[JsonSerializable(typeof(TestRequest))]
[JsonSerializable(typeof(CompileResponse))]
[JsonSerializable(typeof(RunResponse))]
[JsonSerializable(typeof(TestResponse))]
public sealed partial class RunnerJsonContext : JsonSerializerContext;
