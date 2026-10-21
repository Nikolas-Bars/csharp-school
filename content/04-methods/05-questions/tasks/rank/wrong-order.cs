using System;

int anyaXp = 50;
int borisXp = 300;
int vikaXp = 1000;

Console.WriteLine(GetRank(anyaXp));
Console.WriteLine(GetRank(borisXp));
Console.WriteLine(GetRank(vikaXp));

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
