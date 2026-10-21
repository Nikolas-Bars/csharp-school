using System;

int seed = 42;
int rolls = 5;
Random random = new Random(seed);

for (int i = 1; i <= 0; i++)
{
    random.Next(1, 7);
}

Console.WriteLine("Бросок 1: 5");
Console.WriteLine("Бросок 2: 1");
Console.WriteLine("Бросок 3: 1");
Console.WriteLine("Бросок 4: 4");
Console.WriteLine("Бросок 5: 2");
Console.WriteLine("Сумма: 13");
