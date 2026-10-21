using System;

int[] damage = { 25, 40, 18, 52, 33, 45 };
int powerHit = 40;

int total = 0;
int best = damage[0];
int strong = 0;
foreach (int d in damage)
{
    total += d;
    if (d > best)
    {
        best = d;
    }
    if (d >= powerHit)
    {
        strong++;
    }
}
double average = total / damage.Length;

Console.WriteLine($"Раундов: {damage.Length}");
Console.WriteLine($"Всего урона: {total}");
Console.WriteLine($"Средний урон: {average}");
Console.WriteLine($"Лучший удар: {best}");
Console.WriteLine($"Мощных ударов: {strong}");
