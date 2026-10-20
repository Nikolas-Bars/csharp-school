using System.Text;

namespace CSharpSchool.Runner.Execution;

/// <summary>
/// Куда программа ученика печатает. Больше <see cref="limit"/> символов не принимает: цикл,
/// который печатает без конца, иначе съел бы всю память вкладки.
/// </summary>
internal sealed class LimitedWriter(int limit) : TextWriter
{
    private readonly StringBuilder text = new();

    public override Encoding Encoding => Encoding.UTF8;

    public bool LimitExceeded { get; private set; }

    public override void Write(char value) => Append(value.ToString());

    public override void Write(string? value) => Append(value);

    public override void Write(char[] buffer, int index, int count) => Append(new string(buffer, index, count));

    public override string ToString() => text.ToString();

    private void Append(string? value)
    {
        if (string.IsNullOrEmpty(value))
        {
            return;
        }

        var room = limit - text.Length;

        if (value.Length > room)
        {
            text.Append(value, 0, Math.Max(room, 0));
            LimitExceeded = true;
            throw new OutputLimitExceededException();
        }

        text.Append(value);
    }
}

/// <summary>Программа напечатала больше, чем можно.</summary>
internal sealed class OutputLimitExceededException() : Exception("Output limit exceeded");
