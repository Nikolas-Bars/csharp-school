using System;

int[] enemyHp = { 40, 65, 30, 90 };
int number = 3;

Console.WriteLine($"Враг №{number}: {enemyHp[number - 1]}");
Console.WriteLine("Первый и последний вместе: " + enemyHp[0] + enemyHp[3]);
