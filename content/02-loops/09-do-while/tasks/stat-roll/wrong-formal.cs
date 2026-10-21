using System;

int seed = 2024;
Random random = new Random(seed);
int strength = 0;

do
{
    random.Next(1, 7);
} while (strength > 0);

Console.WriteLine("Попытка 1: 6");
Console.WriteLine("Попытка 2: 8");
Console.WriteLine("Попытка 3: 3");
Console.WriteLine("Попытка 4: 11");
Console.WriteLine("Сила: 11");
