using System;

int heroAttack = 10;
int heroLevel = 2;

int damage = CalculateDamage(heroAttack, heroLevel);
Console.WriteLine($"Урон: {damage}");
Console.WriteLine($"Урон: {CalculateDamage(25, 5)}");

static int CalculateDamage(int attack, int level)
{
    int result = attack + level * 3;
}
