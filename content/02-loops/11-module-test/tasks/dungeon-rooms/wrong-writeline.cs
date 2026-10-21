using System;

int seed = 42;
int rooms = 4;
Random random = new Random(seed);
int total = 0;

for (int room = 1; room <= rooms; room++)
{
    int goblins = random.Next(1, 4);
    total += goblins;

    Console.WriteLine($"Комната {room}: ");
    for (int i = 1; i <= goblins; i++)
    {
        Console.WriteLine("Г");
    }
}

Console.WriteLine($"Всего гоблинов: {total}");
