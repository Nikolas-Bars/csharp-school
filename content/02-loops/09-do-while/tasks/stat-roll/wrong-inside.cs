using System;

int seed = 2024;
Random random = new Random(seed);
int tries = 0;

do
{
    int strength = random.Next(1, 7) + random.Next(1, 7);
    tries++;
    Console.WriteLine($"Попытка {tries}: {strength}");
} while (strength < 10);
