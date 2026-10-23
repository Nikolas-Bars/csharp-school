using System;

int bossHp = 300;

Enemy goblin = new Enemy("Гоблин", 40, 5);
Enemy dragon = new Enemy("Дракон", bossHp, 50);
goblin.ShowInfo();
dragon.ShowInfo();

class Enemy
{
    public string name;
    public int hp;
    public int damage;

    public void Enemy(string enemyName, int enemyHp, int enemyDamage)
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
