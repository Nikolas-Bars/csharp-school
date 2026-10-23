using System;

string heroName = "Мира";
int startHp = 80;

Hero hero = new Hero();
hero.name = heroName;
hero.hp = startHp;
Console.WriteLine($"{hero.name}: {hero.hp} HP");

class Hero
{
    public string name;
    public int hp;
}
