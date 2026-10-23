using System;
using System.Collections.Generic;

List<int> wave = new List<int> { 30, 30 };
int reinforcements = 3;

for (int i = 0; i < reinforcements; i++)
{
    wave.Add(50);
}

int total = 0;
foreach (int hp in wave)
{
    total += hp;
}

Console.WriteLine($"Врагов: {wave.Count}");
Console.WriteLine($"Общее здоровье: {total}");
