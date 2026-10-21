using System;
using System.Collections.Generic;

int blast = 50;

List<Enemy> enemies = new List<Enemy>
{
    new Enemy("Гоблин", 30),
    new Enemy("Орк", 60),
    new Enemy("Тролль", 120)
};

int alive = 0;
foreach (Enemy enemy in enemies)
{
    if (enemy.IsAlive())
    {
        alive++;
    }
    enemy.TakeDamage(blast);
    Console.WriteLine($"{enemy.name}: {enemy.hp} HP");
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
