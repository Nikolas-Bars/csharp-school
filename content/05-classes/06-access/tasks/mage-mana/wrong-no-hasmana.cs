using System;

int startMana = 40;

Mage mage = new Mage("Мерлин", startMana);
mage.Cast("Огненный шар", 25);
mage.Cast("Ледяная стрела", 20);
mage.ShowMana();

class Mage
{
    public string name;
    private int mana;

    public Mage(string name, int mana)
    {
        this.name = name;
        this.mana = mana;
    }

    public void Cast(string spell, int cost)
    {
        if (mana >= cost)
        {
            mana -= cost;
            Console.WriteLine($"{name} колдует: {spell}");
        }
        else
        {
            Console.WriteLine($"{name}: мало маны");
        }
    }

    public void ShowMana()
    {
        Console.WriteLine($"Мана: {mana}");
    }

    private bool HasMana(int cost)
    {
        return mana >= cost;
    }
}
