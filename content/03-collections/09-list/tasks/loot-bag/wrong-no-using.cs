using System;

List<string> bag = new List<string>();
bag.Add("монета");
bag.Add("кольцо");
bag.Add("свиток");

Console.WriteLine($"Предметов: {bag.Count}");
foreach (string item in bag)
{
    Console.WriteLine(item);
}
