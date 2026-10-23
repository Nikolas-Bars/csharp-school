using System;

int seed = 42;
int rooms = 4;
Random random = new Random(seed);

for (int room = 1; room <= 0; room++)
{
    int goblins = random.Next(1, 4);
    for (int i = 1; i <= goblins; i++)
    {
    }
}

Console.WriteLine("Комната 1: ГГГ");
Console.WriteLine("Комната 2: Г");
Console.WriteLine("Комната 3: Г");
Console.WriteLine("Комната 4: ГГ");
Console.WriteLine("Всего гоблинов: 7");
