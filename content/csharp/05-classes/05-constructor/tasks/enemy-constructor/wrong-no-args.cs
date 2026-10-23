using System;

int bossHp = 300;

Enemy goblin = new Enemy();
goblin.name = "Гоблин";
goblin.hp = 40;
goblin.damage = 5;
Enemy dragon = new Enemy("Дракон", bossHp, 50);
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
