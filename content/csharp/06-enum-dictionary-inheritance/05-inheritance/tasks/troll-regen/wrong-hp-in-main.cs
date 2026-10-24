using System;

int heal = 15;

Troll troll = new Troll();
troll.name = "Тролль";

troll.Show();
troll.hp = troll.hp + heal;
troll.Show();

class Enemy
{
    public string name;
    protected int hp = 40;

    public void Show()
    {
        Console.WriteLine($"{name}: {hp} HP");
    }
}

class Troll : Enemy
{
    public void Regenerate(int amount)
    {
        hp = Math.Min(hp + amount, 100);
    }
}
