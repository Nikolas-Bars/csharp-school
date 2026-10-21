using System;

int xp = 0;

xp = AddXp(xp, 50);
Console.WriteLine("Опыт: 50");
xp = AddXp(xp, 120);
Console.WriteLine("Опыт: 170");
xp = AddXp(xp, 30);
Console.WriteLine("Опыт: 200");

static int AddXp(int current, int amount)
{
    return current + amount;
}
