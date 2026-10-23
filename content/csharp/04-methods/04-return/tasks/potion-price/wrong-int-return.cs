using System;

double healPrice = 2.5;
int healCount = 3;
double manaPrice = 0.75;
int manaCount = 6;

double healing = TotalPrice(healPrice, healCount);
double mana = TotalPrice(manaPrice, manaCount);
Console.WriteLine($"Лечение: {healing}");
Console.WriteLine($"Мана: {mana}");
Console.WriteLine($"Всего: {healing + mana}");

static int TotalPrice(double price, int count)
{
    return price * count;
}
