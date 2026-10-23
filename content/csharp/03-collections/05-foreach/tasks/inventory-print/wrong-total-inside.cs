using System;

string[] backpack = { "факел", "верёвка", "хлеб", "карта" };

foreach (string item in backpack)
{
    Console.WriteLine($"- {item}");
    Console.WriteLine($"Всего: {backpack.Length}");
}
