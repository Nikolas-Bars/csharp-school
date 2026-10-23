using System;

int hp = 20;
int maxHp = 200;

hp = Heal(hp, 30);
Console.WriteLine($"Здоровье: {hp}");
hp = Heal(hp, maxHp, 25);
Console.WriteLine($"Здоровье: {hp}");
hp = Heal(hp, maxHp, 10);
Console.WriteLine($"Здоровье: {hp}");

static int Heal(int hp, int points)
{
    return hp + points;
}

static int Heal(int hp, int maxHp, int percent)
{
    return hp + maxHp * percent / 100;
}
