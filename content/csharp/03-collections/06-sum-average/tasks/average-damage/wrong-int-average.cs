using System;

int[] hits = { 18, 25, 21, 30 };

int total = 0;
foreach (int h in hits)
{
    total += h;
}
int average = total / hits.Length;

Console.WriteLine($"Сумма: {total}");
Console.WriteLine($"Средний урон: {average}");
