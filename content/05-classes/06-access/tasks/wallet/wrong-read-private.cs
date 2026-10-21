using System;

int startGold = 50;

Wallet wallet = new Wallet(startGold);
if (wallet.Spend(30))
{
    Console.WriteLine("Куплено: зелье");
}
else
{
    Console.WriteLine("Не хватает на зелье");
}
if (wallet.Spend(40))
{
    Console.WriteLine("Куплено: меч");
}
else
{
    Console.WriteLine("Не хватает на меч");
}
Console.WriteLine($"Золото: {wallet.gold}");

class Wallet
{
    private int gold;

    public Wallet(int gold)
    {
        this.gold = gold;
    }

    public bool Spend(int price)
    {
        if (gold >= price)
        {
            gold -= price;
            return true;
        }
        return false;
    }

    public int GetGold()
    {
        return gold;
    }
}
