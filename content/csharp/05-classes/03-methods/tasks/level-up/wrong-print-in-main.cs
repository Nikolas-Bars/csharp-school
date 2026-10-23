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
Console.WriteLine($"{hero.name}, уровень {hero.level}: {hero.hp} HP");

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
}
