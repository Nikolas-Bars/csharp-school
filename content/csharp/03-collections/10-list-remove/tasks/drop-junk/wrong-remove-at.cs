using System;
using System.Collections.Generic;

List<string> bag = new List<string> { "кость", "меч", "кость", "зелье", "кость" };
string junk = "кость";

while (bag.Contains(junk))
{
    bag.RemoveAt(0);
}

foreach (string item in bag)
{
    Console.WriteLine(item);
}
Console.WriteLine($"Осталось предметов: {bag.Count}");
