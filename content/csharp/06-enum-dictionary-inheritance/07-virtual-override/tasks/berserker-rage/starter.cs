using System;
using System.Collections.Generic;

int berserkerHp = 15;

List<Enemy> party = new List<Enemy>();
party.Add(new Enemy("Скелет", 30, 4));
// Добавь в отряд берсерка

foreach (Enemy enemy in party)
{
    enemy.Attack();
}

class Enemy
{
    public string name;
    protected int hp;
    protected int damage;

    public Enemy(string name, int hp, int damage)
    {
        this.name = name;
        this.hp = hp;
        this.damage = damage;
    }

    public virtual void Attack()
    {
        Console.WriteLine($"{name} бьёт: {damage} урона");
    }
}

// Ниже объяви наследника Berserker
