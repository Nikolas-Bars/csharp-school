using System.Text.Json;
using System.Text.Json.Serialization;

namespace CSharpSchool.Runner.Protocol;

/// <summary>Значения перечислений в JSON — строками в camelCase, как и поля: «exact», «doWhile».</summary>
public sealed class CamelCaseEnumConverter<TEnum>() : JsonStringEnumConverter<TEnum>(JsonNamingPolicy.CamelCase, allowIntegerValues: false)
    where TEnum : struct, Enum;
