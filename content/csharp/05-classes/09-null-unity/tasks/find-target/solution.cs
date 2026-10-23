using System;
using System.Collections.Generic;

string targetName = "Орк";

List<Enemy> enemies = new List<Enemy>
{
    new Enemy("Гоблин", 30),
    new Enemy("Орк", 60),
    new Enemy("Тролль", 90)
};

Enemy target = FindEnemy(enemies, targetName);
if (target != null)
{
    Console.WriteLine($"Цель: {target.name}, {target.hp} HP");
}
else
{
    Console.WriteLine($"Цель не найдена: {targetName}");
}

static Enemy FindEnemy(List<Enemy> enemies, string name)
{
    foreach (Enemy enemy in enemies)
    {
        if (enemy.name == name)
        {
            return enemy;
        }
    }
    return null;
}

class Enemy
{
    public string name;
    public int hp;

    public Enemy(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }
}
