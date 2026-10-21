using System;

int seed = 1;
Random random = new Random(seed);
int roll;
int tries = 0;

do
{
    roll = random.Next(1, 21);
    tries++;
    Console.WriteLine($"Попытка {tries}: {roll}");
} while (roll < 15 && tries < 5);

if (roll >= 15)
{
    Console.WriteLine("Замок открыт!");
}
else
{
    Console.WriteLine("Отмычки сломались.");
}
