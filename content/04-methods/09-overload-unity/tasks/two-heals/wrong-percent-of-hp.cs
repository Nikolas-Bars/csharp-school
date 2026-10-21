using System;

int hp = 20;
int maxHp = 200;

hp = HealPoints(hp, 30);
Console.WriteLine($"Здоровье: {hp}");
hp = HealPercent(hp, maxHp, 25);
Console.WriteLine($"Здоровье: {hp}");
hp = HealPercent(hp, maxHp, 10);
Console.WriteLine($"Здоровье: {hp}");

static int HealPoints(int hp, int points)
{
    return hp + points;
}

static int HealPercent(int hp, int maxHp, int percent)
{
    return hp + hp * percent / 100;
}
