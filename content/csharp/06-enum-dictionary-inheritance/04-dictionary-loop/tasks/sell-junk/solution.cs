using System;
using System.Collections.Generic;

int bonePrice = 2;

Dictionary<string, int> prices = new Dictionary<string, int>();
prices["кость"] = bonePrice;
prices["тряпка"] = 1;
prices["шкура"] = 5;

Dictionary<string, int> bag = new Dictionary<string, int>();
bag["меч"] = 1;
bag["кость"] = 4;
bag["зелье"] = 2;
bag["тряпка"] = 3;

int gold = 0;

List<string> sold = new List<string>();
foreach (KeyValuePair<string, int> pair in bag)
{
    if (prices.TryGetValue(pair.Key, out int price))
    {
        gold += price * pair.Value;
        sold.Add(pair.Key);
    }
}

foreach (string item in sold)
{
    bag.Remove(item);
}

Console.WriteLine($"Золото: {gold}");
foreach (KeyValuePair<string, int> pair in bag)
{
    Console.WriteLine($"{pair.Key}: {pair.Value}");
}
