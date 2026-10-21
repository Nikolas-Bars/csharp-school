using System;
using System.Collections.Generic;

string[] bag = { "монета", "кольцо", "свиток" };

Console.WriteLine($"Предметов: {bag.Length}");
foreach (string item in bag)
{
    Console.WriteLine(item);
}
