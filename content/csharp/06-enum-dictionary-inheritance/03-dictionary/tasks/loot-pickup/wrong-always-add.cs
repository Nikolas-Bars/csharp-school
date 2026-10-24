using System;
using System.Collections.Generic;

string found = "стрела";
int amount = 5;

Dictionary<string, int> inventory = new Dictionary<string, int>();
inventory["зелье"] = 2;
inventory["стрела"] = 10;

inventory.Add(found, amount);
Console.WriteLine($"{found}: {inventory[found]}");
Console.WriteLine($"Видов предметов: {inventory.Count}");
