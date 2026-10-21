using System;

Console.WriteLine(GetRank(50));
Console.WriteLine(GetRank(300));
Console.WriteLine(GetRank(1200));

static string GetRank(int xp)
{
    if (xp >= 300)
    {
        return "Ветеран";
    }
    if (xp >= 1000)
    {
        return "Легенда";
    }
    return "Новичок";
}
