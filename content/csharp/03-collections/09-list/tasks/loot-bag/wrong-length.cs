using System;
using System.Collections.Generic;

List<string> bag = new List<string>();
bag.Add("монета");
bag.Add("кольцо");
bag.Add("свиток");

Console.WriteLine($"Предметов: {bag.Length}");
foreach (string item in bag)
{
    Console.WriteLine(item);
}
