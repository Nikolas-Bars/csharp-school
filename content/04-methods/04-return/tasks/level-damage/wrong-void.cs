using System;

int heroAttack = 10;
int heroLevel = 2;

int damage = CalculateDamage(heroAttack, heroLevel);
Console.WriteLine($"Урон: {damage}");
Console.WriteLine($"Урон: {CalculateDamage(25, 5)}");

static void CalculateDamage(int attack, int level)
{
    Console.WriteLine(attack + level * 3);
}
