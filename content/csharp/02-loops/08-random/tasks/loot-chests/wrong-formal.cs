using System;

int seed = 100;
int chests = 8;
Random random = new Random(seed);

for (int chest = 1; chest <= 0; chest++)
{
    if (random.Next(1, 101) > 80)
    {
    }
}

Console.WriteLine("Сундук 1: редкий лут! (97)");
Console.WriteLine("Сундук 4: редкий лут! (91)");
Console.WriteLine("Сундук 6: редкий лут! (95)");
Console.WriteLine("Редких находок: 3");
