using System;

int hit = 10;
int orcRage = 3;

Orc orc = new Orc();
orc.name = "Грох";
orc.hp = 60;
orc.rage = orcRage;

Goblin goblin = new Goblin();
goblin.name = "Скрип";
goblin.hp = 25;

Console.WriteLine("Грох: 50 HP");
Console.WriteLine("Скрип: 15 HP");
Console.WriteLine("Грох рычит! Ярость: 3");
Console.WriteLine("Скрип крадёт монету");

class Enemy
{
    public string name;
    public int hp;

    public void TakeDamage(int damage)
    {
        hp = Math.Max(hp - damage, 0);
    }

    public void Info()
    {
        Console.WriteLine($"{name}: {hp} HP");
    }
}

class Orc : Enemy
{
    public int rage;

    public void Roar()
    {
        Console.WriteLine($"{name} рычит! Ярость: {rage}");
    }
}

class Goblin : Enemy
{
    public void Steal()
    {
        Console.WriteLine($"{name} крадёт монету");
    }
}
