using System;

string item = "меч";
int gold = 100;

if (item == "меч")
{
    gold -= 60;
    Console.WriteLine("Куплен меч");
}
else if (item == "щит")
{
    gold -= 40;
    Console.WriteLine("Куплен щит");
}
else if (item == "зелье")
{
    gold -= 15;
    Console.WriteLine("Куплено зелье");
}
else
{
    Console.WriteLine("Такого товара нет");
}

Console.WriteLine($"Золото: {gold}");
