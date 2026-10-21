using System;
using System.Collections.Generic;

int blast = 50;

List<Enemy> enemies = new List<Enemy>();
enemies.Add("Гоблин", 30);
enemies.Add("Орк", 60);
enemies.Add("Тролль", 120);

foreach (Enemy enemy in enemies)
{
    enemy.TakeDamage(blast);
}

int alive = 0;
foreach (Enemy enemy in enemies)
{
    Console.WriteLine($"{enemy.name}: {enemy.hp} HP");
    if (enemy.IsAlive())
    {
        alive++;
    }
}
Console.WriteLine($"Живых врагов: {alive}");

class Enemy
{
    public string name;
    public int hp;

    public Enemy(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }

    public void TakeDamage(int damage)
    {
        hp = Math.Max(hp - damage, 0);
    }

    public bool IsAlive()
    {
        return hp > 0;
    }
}
