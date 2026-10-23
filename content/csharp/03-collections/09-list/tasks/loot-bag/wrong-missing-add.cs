using System;
using System.Collections.Generic;

List<string> bag = new List<string>();
bag.Add("монета");
bag.Add("свиток");

Console.WriteLine($"Предметов: {bag.Count}");
foreach (string item in bag)
{
    Console.WriteLine(item);
}
