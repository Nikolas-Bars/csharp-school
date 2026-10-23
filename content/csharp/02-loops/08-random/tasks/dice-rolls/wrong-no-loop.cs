using System;

int seed = 42;
int rolls = 5;
Random random = new Random(seed);
int a = random.Next(1, 7);
int b = random.Next(1, 7);
int c = random.Next(1, 7);
int d = random.Next(1, 7);
int e = random.Next(1, 7);

Console.WriteLine($"Бросок 1: {a}");
Console.WriteLine($"Бросок 2: {b}");
Console.WriteLine($"Бросок 3: {c}");
Console.WriteLine($"Бросок 4: {d}");
Console.WriteLine($"Бросок 5: {e}");
Console.WriteLine($"Сумма: {a + b + c + d + e}");
