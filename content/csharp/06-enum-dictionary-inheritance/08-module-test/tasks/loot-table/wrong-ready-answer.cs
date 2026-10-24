using System;
using System.Collections.Generic;

string[] defeated = { "Слизень", "Скелет", "Слизень", "Дракон" };

List<Enemy> enemies = new List<Enemy>();
foreach (string name in defeated)
{
    if (name == "Слизень")
    {
        enemies.Add(new Slime());
    }
    else if (name == "Дракон")
    {
        enemies.Add(new Dragon());
    }
    else
    {
        enemies.Add(new Enemy());
    }
}

Dictionary<string, int> loot = new Dictionary<string, int>();
foreach (Enemy enemy in enemies)
{
    string item = enemy.Loot();
    if (loot.ContainsKey(item))
    {
        loot[item]++;
    }
    else
    {
        loot.Add(item, 1);
    }
}

Console.WriteLine("слизь: 2");
Console.WriteLine("кость: 1");
Console.WriteLine("чешуя: 1");

class Enemy
{
    public virtual string Loot()
    {
        return "кость";
    }
}

class Slime : Enemy
{
    public override string Loot()
    {
        return "слизь";
    }
}

class Dragon : Enemy
{
    public override string Loot()
    {
        return "чешуя";
    }
}
