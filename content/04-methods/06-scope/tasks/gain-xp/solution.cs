using System;

int xp = 0;

xp = AddXp(xp, 50);
Console.WriteLine($"Опыт: {xp}");
xp = AddXp(xp, 120);
Console.WriteLine($"Опыт: {xp}");
xp = AddXp(xp, 30);
Console.WriteLine($"Опыт: {xp}");

static int AddXp(int current, int amount)
{
    return current + amount;
}
