using System;
using System.Collections.Generic;

string[] wave = { "Орк", "Гоблин", "Крыса" };

List<Enemy> enemies = new List<Enemy>();
foreach (string kind in wave)
{
    if (kind == "Орк")
    {
        enemies.Add(new Orc(kind));
    }
    else if (kind == "Гоблин")
    {
        enemies.Add(new Goblin(kind));
    }
    else
    {
        enemies.Add(new Enemy(kind));
    }
}

Console.WriteLine("Орк бьёт дубиной: 12 урона");
Console.WriteLine("Гоблин кидает камень: 4 урона");
Console.WriteLine("Крыса кусает: 2 урона");

class Enemy
{
    public string name;

    public Enemy(string name)
    {
        this.name = name;
    }

    public virtual void Attack()
    {
        Console.WriteLine($"{name} кусает: 2 урона");
    }
}

class Orc : Enemy
{
    public Orc(string name) : base(name)
    {
    }

    public override void Attack()
    {
        Console.WriteLine($"{name} бьёт дубиной: 12 урона");
    }
}

class Goblin : Enemy
{
    public Goblin(string name) : base(name)
    {
    }

    public override void Attack()
    {
        Console.WriteLine($"{name} кидает камень: 4 урона");
    }
}
