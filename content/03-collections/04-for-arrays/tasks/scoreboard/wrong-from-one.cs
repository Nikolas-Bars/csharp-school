using System;

string[] names = { "Аня", "Борис", "Вика", "Глеб" };
int[] scores = { 300, 250, 180, 90 };

for (int i = 1; i < names.Length; i++)
{
    Console.WriteLine($"{i + 1}. {names[i]}: {scores[i]}");
}
