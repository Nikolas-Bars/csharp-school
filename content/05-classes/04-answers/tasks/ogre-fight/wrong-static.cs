using System;

int attack = 12;

Enemy ogre = new Enemy();
ogre.name = "Огр";
ogre.hp = 50;

int hits = 0;
while (Enemy.IsAlive())
{
    ogre.TakeDamage(attack);
    hits++;
}
Console.WriteLine($"Ударов до победы: {hits}");

class Enemy
{
    public string name;
    public int hp;

    public void TakeDamage(int damage)
    {
        hp -= damage;
    }

    public static bool IsAlive()
    {
        return hp > 0;
    }
}
