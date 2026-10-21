using System;
using System.Collections.Generic;

List<string> bag = new List<string> { "кость", "меч", "кость", "зелье", "кость" };
string junk = "кость";

if (bag.Contains(junk))
{
    bag.Remove(junk);
}

foreach (string item in bag)
{
    Console.WriteLine(item);
}
Console.WriteLine($"Осталось предметов: {bag.Count}");
