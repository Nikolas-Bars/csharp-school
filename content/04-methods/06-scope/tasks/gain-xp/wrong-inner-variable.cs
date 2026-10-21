using System;

int xp = 0;

AddXp(xp, 50);
Console.WriteLine($"Опыт: {total}");

static int AddXp(int current, int amount)
{
    int total = current + amount;
    return total;
}
