using System;
using System.Collections.Generic;

string item = "щит";

Dictionary<string, int> prices = new Dictionary<string, int>();
prices.Add("меч", 50);
prices.Add("щит", 35);
prices.Add("зелье", 10);

Console.WriteLine($"{item}: {prices["щит"]} золотых");
Console.WriteLine($"Товаров: {prices.Count}");
