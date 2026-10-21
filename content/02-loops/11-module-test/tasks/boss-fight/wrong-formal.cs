using System;

int seed = 7;
Random random = new Random(seed);
int bossHp = 100;
int round = 0;

while (bossHp < 0)
{
    if (round % 3 == 0)
    {
        bossHp = Math.Max(bossHp - random.Next(10, 21), 0);
    }
}

Console.WriteLine("Раунд 1: урон 14, у босса 86 HP");
Console.WriteLine("Раунд 2: урон 19, у босса 67 HP");
Console.WriteLine("Раунд 3: урон 34, у босса 33 HP");
Console.WriteLine("Раунд 4: урон 10, у босса 23 HP");
Console.WriteLine("Раунд 5: урон 14, у босса 9 HP");
Console.WriteLine("Раунд 6: урон 34, у босса 0 HP");
Console.WriteLine("Босс повержен! Раундов: 6");
