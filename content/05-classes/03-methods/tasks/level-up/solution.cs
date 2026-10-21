using System;

int wins = 3;

Hero hero = new Hero();
hero.name = "Мира";
hero.level = 1;
hero.hp = 50;

for (int i = 0; i < wins; i++)
{
    hero.LevelUp();
}
hero.ShowInfo();

class Hero
{
    public string name;
    public int level;
    public int hp;

    public void LevelUp()
    {
        level++;
        hp += 10;
    }

    public void ShowInfo()
    {
        Console.WriteLine($"{name}, уровень {level}: {hp} HP");
    }
}
