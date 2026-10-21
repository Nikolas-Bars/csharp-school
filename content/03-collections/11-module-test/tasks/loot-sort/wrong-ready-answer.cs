using System;
using System.Collections.Generic;

string[] drops = { "монета", "кость", "зелье", "меч", "кость", "монета" };
string junk = "кость";
List<string> bag = new List<string>();

foreach (string item in drops)
{
    if (item != junk)
    {
        bag.Add(item);
    }
}

if (bag.Contains("зелье"))
{
    bag.Remove("зелье");
    Console.WriteLine("Выпито зелье");
}

Console.WriteLine("1. монета");
Console.WriteLine("2. меч");
Console.WriteLine("3. монета");
Console.WriteLine($"В рюкзаке: {bag.Count}");
