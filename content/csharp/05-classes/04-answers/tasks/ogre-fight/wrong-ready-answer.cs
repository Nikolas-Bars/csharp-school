using System;

int attack = 12;

Enemy ogre = new Enemy();
ogre.name = "Огр";
ogre.hp = 50;

while (ogre.IsAlive())
{
    ogre.TakeDamage(attack);
}
Console.WriteLine("Ударов до победы: 5");

class Enemy
{
    public string name;
    public int hp;

    public void TakeDamage(int damage)
    {
        hp -= damage;
    }

    public bool IsAlive()
    {
        return hp > 0;
    }
}
