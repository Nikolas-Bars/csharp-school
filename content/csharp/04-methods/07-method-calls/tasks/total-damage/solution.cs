using System;

Console.WriteLine(TotalDamage(10, 5));
Console.WriteLine(TotalDamage(7, 12));

static int BaseDamage(int strength)
{
    return strength * 2;
}

static int TotalDamage(int strength, int weaponBonus)
{
    return BaseDamage(strength) + weaponBonus;
}
