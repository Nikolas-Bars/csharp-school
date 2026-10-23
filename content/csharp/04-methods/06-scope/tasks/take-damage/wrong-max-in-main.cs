using System;

int hp = 50;

hp = Math.Max(TakeDamage(hp, 20), 0);
Console.WriteLine(hp);
hp = Math.Max(TakeDamage(hp, 45), 0);
Console.WriteLine(hp);

static int TakeDamage(int hp, int damage)
{
    return hp - damage;
}
