using System;
using System.Collections.Generic;

string[] drops = { "монета", "кость", "зелье", "меч", "кость", "монета" };
string junk = "кость";
List<string> bag = new List<string>();

foreach (string item in drops)
{
    if (item == junk)
    {
        bag.Add(item);
    }
}

if (bag.Contains("зелье"))
{
    bag.Remove("зелье");
    Console.WriteLine("Выпито зелье");
}

for (int i = 0; i < bag.Count; i++)
{
    Console.WriteLine($"{i + 1}. {bag[i]}");
}
Console.WriteLine($"В рюкзаке: {bag.Count}");
