using System;

int seed = 100;
int chests = 8;
int rare = 0;

for (int chest = 1; chest <= chests; chest++)
{
    Random random = new Random(seed);
    int roll = random.Next(1, 101);
    if (roll > 80)
    {
        Console.WriteLine($"Сундук {chest}: редкий лут! ({roll})");
        rare++;
    }
}

Console.WriteLine($"Редких находок: {rare}");
