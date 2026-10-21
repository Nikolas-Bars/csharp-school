using System;

string item = "меч";
int gold = 100;

switch (item)
{
    case "Меч":
        gold -= 60;
        Console.WriteLine("Куплен меч");
        break;
    case "Щит":
        gold -= 40;
        Console.WriteLine("Куплен щит");
        break;
    case "Зелье":
        gold -= 15;
        Console.WriteLine("Куплено зелье");
        break;
    default:
        Console.WriteLine("Такого товара нет");
        break;
}

Console.WriteLine($"Золото: {gold}");
