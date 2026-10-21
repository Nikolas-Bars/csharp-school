using System;

Console.WriteLine(CanEnter(12, true));
Console.WriteLine(CanEnter(8, true));
Console.WriteLine(CanEnter(15, false));

static bool CanEnter(int level, bool hasKey)
{
    if (level >= 10 && hasKey)
    {
        return true;
    }
}
