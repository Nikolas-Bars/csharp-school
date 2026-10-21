using System;

int seed = 2024;
Random random = new Random(seed);
int strength = 0;
int tries = 0;

while (strength < 10)
{
    strength = random.Next(1, 7) + random.Next(1, 7);
    tries++;
    Console.WriteLine($"Попытка {tries}: {strength}");
}

Console.WriteLine($"Сила: {strength}");
