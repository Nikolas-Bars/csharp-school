using System;

int[] enemyHp = { 65, 40, 85, 25, 50 };

for (int i = 0; i < enemyHp.Length; i++)
{
    if (enemyHp[i] < 0)
    {
        Console.WriteLine("Этого не будет");
    }
}
Console.WriteLine("Цель: враг №4, 25 HP");
