using System;

string item = "меч";
int gold = 100;

switch (item)
{
    case "меч":
        gold -= 60;
        Console.WriteLine("Куплен меч");
        break;
    case "щит":
        gold -= 40;
        Console.WriteLine("Куплен щит");
        break;
    case "зелье":
        gold -= 15;
        Console.WriteLine("Куплено зелье");
        break;
    default:
        Console.WriteLine("Такого товара нет");
        break;
}

Console.WriteLine($"Золото: {gold}");
