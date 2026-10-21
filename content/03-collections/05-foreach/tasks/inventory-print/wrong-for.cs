using System;

string[] backpack = { "факел", "верёвка", "хлеб", "карта" };

for (int i = 0; i < backpack.Length; i++)
{
    Console.WriteLine($"- {backpack[i]}");
}
Console.WriteLine($"Всего: {backpack.Length}");
