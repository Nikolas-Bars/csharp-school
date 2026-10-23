using System;

int seed = 1;
Random random = new Random(seed);
int roll = 0;

do
{
    roll = 16;
} while (roll < 15);

Console.WriteLine("Попытка 1: 5");
Console.WriteLine("Попытка 2: 3");
Console.WriteLine("Попытка 3: 10");
Console.WriteLine("Попытка 4: 16");

if (roll >= 15)
{
    Console.WriteLine("Замок открыт!");
}
else
{
    Console.WriteLine("Отмычки сломались.");
}
