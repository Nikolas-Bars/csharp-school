using System;

int bossHp = 70;
int armor = 10;
int[] attacks = { 30, 8, 45, 25, 40 };

int damage = CalculateDamage(attacks[0], armor);
bossHp -= damage;
if (IsAlive(bossHp))
{
    Console.WriteLine("Удар 30: урон 20, у босса 50 HP");
    Console.WriteLine("Удар 8: урон 1, у босса 49 HP");
    Console.WriteLine("Удар 45: урон 35, у босса 14 HP");
    Console.WriteLine("Удар 25: урон 15, у босса 0 HP");
    Console.WriteLine("Босс повержен!");
}

static int CalculateDamage(int attack, int armor)
{
    return Math.Max(attack - armor, 1);
}

static bool IsAlive(int hp)
{
    return hp > 0;
}
