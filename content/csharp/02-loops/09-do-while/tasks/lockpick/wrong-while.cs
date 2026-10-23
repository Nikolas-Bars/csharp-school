using System;

int seed = 1;
Random random = new Random(seed);
int roll = 0;
int tries = 0;

while (roll < 15 && tries < 5)
{
    roll = random.Next(1, 21);
    tries++;
    Console.WriteLine($"Попытка {tries}: {roll}");
}

if (roll >= 15)
{
    Console.WriteLine("Замок открыт!");
}
else
{
    Console.WriteLine("Отмычки сломались.");
}
