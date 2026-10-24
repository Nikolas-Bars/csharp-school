using System;

int armor = 5;
int hit = 12;

ArmoredEnemy knight = new ArmoredEnemy("Чёрный рыцарь", 50, armor);
Console.WriteLine("Чёрный рыцарь: 43 HP");
Console.WriteLine("Чёрный рыцарь: 43 HP");

class Enemy
{
    public string name;
    protected int hp;

    public Enemy(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }

    public void TakeDamage(int damage)
    {
        hp = Math.Max(hp - damage, 0);
    }

    public void Info()
    {
        Console.WriteLine($"{name}: {hp} HP");
    }
}

class ArmoredEnemy : Enemy
{
    private int armor;

    public ArmoredEnemy(string name, int hp, int armor) : base(name, hp)
    {
        this.armor = armor;
    }

    public void Block(int damage)
    {
        TakeDamage(Math.Max(damage - armor, 0));
    }
}
