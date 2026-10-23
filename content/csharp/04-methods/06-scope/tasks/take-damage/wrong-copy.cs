using System;

int hp = 50;

TakeDamage(hp, 20);
Console.WriteLine(hp);
TakeDamage(hp, 45);
Console.WriteLine(hp);

static void TakeDamage(int hp, int damage)
{
    hp = Math.Max(hp - damage, 0);
}
