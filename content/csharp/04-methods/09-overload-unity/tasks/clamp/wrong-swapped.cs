using System;

Console.WriteLine(Clamp(120, 0, 100));
Console.WriteLine(Clamp(-15, 0, 100));
Console.WriteLine(Clamp(60, 0, 100));

static int Clamp(int value, int min, int max)
{
    if (value < min)
    {
        return max;
    }
    if (value > max)
    {
        return min;
    }
    return value;
}
