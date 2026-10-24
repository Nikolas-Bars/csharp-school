using System;
using System.Collections.Generic;

string item = "щит";

int price = 0;
if (item == "меч")
{
    price = 50;
}
else if (item == "щит")
{
    price = 35;
}
else if (item == "зелье")
{
    price = 10;
}
Console.WriteLine($"{item}: {price} золотых");
Console.WriteLine("Товаров: 3");
