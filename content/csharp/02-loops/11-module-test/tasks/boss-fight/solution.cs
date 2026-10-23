using System;

int seed = 7;
Random random = new Random(seed);
int bossHp = 100;
int round = 0;

while (bossHp > 0)
{
    round++;
    int damage = random.Next(10, 21);

    if (round % 3 == 0)
    {
        damage *= 2;
    }

    bossHp = Math.Max(bossHp - damage, 0);
    Console.WriteLine($"Раунд {round}: урон {damage}, у босса {bossHp} HP");
}

Console.WriteLine($"Босс повержен! Раундов: {round}");
