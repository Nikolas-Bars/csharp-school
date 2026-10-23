using System;

int bossHp = 300;

Enemy goblin = new Enemy("Гоблин", 5, 40);
Enemy dragon = new Enemy("Дракон", 50, bossHp);
goblin.ShowInfo();
dragon.ShowInfo();

class Enemy
{
    public string name;
    public int hp;
    public int damage;

    public Enemy(string enemyName, int enemyHp, int enemyDamage)
    {
        name = enemyName;
        hp = enemyHp;
        damage = enemyDamage;
    }

    public void ShowInfo()
    {
        Console.WriteLine($"{name}: {hp} HP, урон {damage}");
    }
}
