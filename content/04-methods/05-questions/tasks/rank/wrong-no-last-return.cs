using System;

Console.WriteLine(GetRank(50));
Console.WriteLine(GetRank(300));
Console.WriteLine(GetRank(1200));

static string GetRank(int xp)
{
    if (xp >= 1000)
    {
        return "Легенда";
    }
    if (xp >= 300)
    {
        return "Ветеран";
    }
}
