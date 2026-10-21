using System;

int bossHp = 70;
int armor = 10;
int[] attacks = { 30, 8, 45, 25, 40 };

foreach (int attack in attacks)
{
    int damage = CalculateDamage(attack, armor);
    bossHp = Math.Max(bossHp - damage, 0);
    Console.WriteLine($"Удар {attack}: урон {damage}, у босса {bossHp} HP");
    if (!IsAlive(bossHp))
    {
        Console.WriteLine("Босс повержен!");
        break;
    }
}

static int CalculateDamage(int attack, int armor)
{
    return attack - armor;
}

static bool IsAlive(int hp)
{
    return hp > 0;
}
