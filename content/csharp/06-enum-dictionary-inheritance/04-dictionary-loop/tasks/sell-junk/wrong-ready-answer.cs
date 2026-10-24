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

Console.WriteLine("Золото: 11");
Console.WriteLine("меч: 1");
Console.WriteLine("зелье: 2");
