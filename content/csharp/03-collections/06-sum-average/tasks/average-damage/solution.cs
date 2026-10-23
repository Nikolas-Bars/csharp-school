using System;

int[] hits = { 18, 25, 21, 30 };

double total = 0;
foreach (int h in hits)
{
    total += h;
}
double average = total / hits.Length;

Console.WriteLine($"Сумма: {total}");
Console.WriteLine($"Средний урон: {average}");
