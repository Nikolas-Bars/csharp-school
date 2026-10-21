using System;

int hp = 50;

hp = TakeDamage(hp, 20);
Console.WriteLine(hp);
hp = TakeDamage(hp, 45);
Console.WriteLine(hp);

static int TakeDamage(int hp, int damage)
{
    return Math.Max(hp - damage, 0);
}
