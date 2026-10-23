using System;

int gold = 75;
int level = 4;
int swordPrice = 60;
int minLevel = 5;
int potionPrice = 20;

if (gold >= swordPrice || level >= minLevel)
{
    Console.WriteLine("Куплен меч");
    gold -= swordPrice;
}
else if (gold >= potionPrice)
{
    Console.WriteLine("Куплено зелье");
    gold -= potionPrice;
}
else
{
    Console.WriteLine("Ничего не куплено");
}

Console.WriteLine($"Осталось золота: {gold}");
