using System;

int seed = 42;
int rolls = 5;
Random random = new Random(seed);
int sum = 0;

for (int i = 1; i <= rolls; i++)
{
    int roll = random.Next(1, 7);
    Console.WriteLine($"Бросок {i}: {roll}");
    sum += roll;
}

Console.WriteLine($"Сумма: {sum}");
