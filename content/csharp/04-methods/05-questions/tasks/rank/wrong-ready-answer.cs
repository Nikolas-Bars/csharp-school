using System;

int anyaXp = 50;
int borisXp = 300;
int vikaXp = 1000;

GetRank(anyaXp);
Console.WriteLine("Новичок");
Console.WriteLine("Ветеран");
Console.WriteLine("Легенда");

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
    return "Новичок";
}
