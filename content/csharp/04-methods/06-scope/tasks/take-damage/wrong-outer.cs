using System;

int hp = 50;

hp = TakeDamage(20);
Console.WriteLine(hp);
hp = TakeDamage(45);
Console.WriteLine(hp);

static int TakeDamage(int damage)
{
    return Math.Max(hp - damage, 0);
}
