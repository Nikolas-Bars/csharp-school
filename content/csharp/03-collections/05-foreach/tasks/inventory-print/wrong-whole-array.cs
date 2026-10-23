using System;

string[] backpack = { "факел", "верёвка", "хлеб", "карта" };

foreach (string item in backpack)
{
    Console.WriteLine($"- {backpack}");
}
Console.WriteLine($"Всего: {backpack.Length}");
