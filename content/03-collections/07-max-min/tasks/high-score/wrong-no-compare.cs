using System;

int[] scores = { 450, 1200, 830, 1570, 990 };

int best = scores[0];
foreach (int s in scores)
{
    best = s;
}
Console.WriteLine($"Рекорд: {best}");
