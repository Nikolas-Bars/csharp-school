using System;

Console.WriteLine(TotalDamage(BaseDamage(10), 5));
Console.WriteLine(TotalDamage(BaseDamage(7), 12));

static int BaseDamage(int strength)
{
    return strength * 2;
}

static int TotalDamage(int baseDamage, int weaponBonus)
{
    return baseDamage + weaponBonus;
}
