using System;

int reward = 130;

Hero hero = new Hero("Мира");
hero.AddXp(reward);
Console.WriteLine($"{hero.Name}: уровень {hero.Level}, опыт {hero.Xp}/100");
hero.AddXp(250);
Console.WriteLine($"{hero.Name}: уровень {hero.Level}, опыт {hero.Xp}/100");

class Hero
{
    public string Name { get; private set; }
    public int Level { get; private set; }
    public int Xp { get; private set; }

    public Hero(string name)
    {
        Name = name;
        Level = 1;
    }

    public void AddXp(int amount)
    {
        Xp += amount;
        while (Xp >= 100)
        {
            Xp -= 100;
            Level++;
        }
    }
}
