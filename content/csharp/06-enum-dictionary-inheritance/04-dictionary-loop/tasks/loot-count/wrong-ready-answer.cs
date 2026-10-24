using System;
using System.Collections.Generic;

string[] loot = { "монета", "зелье", "монета", "кость", "монета", "зелье" };

Dictionary<string, int> counts = new Dictionary<string, int>();
foreach (string item in loot)
{
    if (counts.ContainsKey(item))
    {
        counts[item]++;
    }
    else
    {
        counts.Add(item, 1);
    }
}

Console.WriteLine("монета: 3");
Console.WriteLine("зелье: 2");
Console.WriteLine("кость: 1");
