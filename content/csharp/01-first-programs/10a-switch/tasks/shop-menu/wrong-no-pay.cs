using System;

string item = "меч";
int gold = 100;

switch (item)
{
    case "меч":
        Console.WriteLine("Куплен меч");
        break;
    case "щит":
        Console.WriteLine("Куплен щит");
        break;
    case "зелье":
        Console.WriteLine("Куплено зелье");
        break;
    default:
        Console.WriteLine("Такого товара нет");
        break;
}

Console.WriteLine($"Золото: {gold}");
