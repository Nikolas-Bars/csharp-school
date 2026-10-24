using System;
using System.Collections.Generic;

int berserkerHp = 15;

List<Enemy> party = new List<Enemy>();
party.Add(new Enemy("Скелет", 30, 4));
party.Add(new Berserker("Берсерк", berserkerHp));

Console.WriteLine("Скелет бьёт: 4 урона");
Console.WriteLine("Берсерк бьёт: 9 урона");
Console.WriteLine("Берсерк в ярости!");
Console.WriteLine("Берсерк бьёт: 9 урона");

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

class Berserker : Enemy
{
    public Berserker(string name, int hp) : base(name, hp, 9)
    {
    }

    public override void Attack()
    {
        base.Attack();
        if (hp < 20)
        {
            Console.WriteLine($"{name} в ярости!");
            base.Attack();
        }
    }
}
