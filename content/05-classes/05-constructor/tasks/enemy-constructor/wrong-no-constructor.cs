using System;

int bossHp = 300;

Enemy goblin = new Enemy();
goblin.name = "Гоблин";
goblin.hp = 40;
goblin.damage = 5;
Enemy dragon = new Enemy();
dragon.name = "Дракон";
dragon.hp = bossHp;
dragon.damage = 50;
goblin.ShowInfo();
dragon.ShowInfo();

class Enemy
{
    public string name;
    public int hp;
    public int damage;

    public void ShowInfo()
    {
        Console.WriteLine($"{name}: {hp} HP, урон {damage}");
    }
}
