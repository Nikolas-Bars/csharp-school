using System;

int[] enemyHp = { 65, 40, 85, 25, 50 };

int weakest = enemyHp[0];
int weakestIndex = 0;
for (int i = 0; i < enemyHp.Length; i++)
{
    if (enemyHp[i] < weakest)
    {
        weakest = enemyHp[i];
        weakestIndex = i;
    }
}
Console.WriteLine($"Цель: враг №{weakestIndex + 1}, {weakest} HP");
