using System;

int goblinHp = 60;
int[] hits = { 15, 30, 25 };

Enemy goblin = new Enemy();
goblin.name = "Гоблин";
goblin.hp = goblinHp;

foreach (int hit in hits)
{
    goblin.TakeDamage(hit);
    goblin.ShowInfo();
}

class Enemy
{
    public string name;
    public int hp;

    public void TakeDamage(int damage)
    {
        hp = Math.Max(hp - damage, 0);
    }

    public void ShowInfo()
    {
        Console.WriteLine($"{name}: {hp} HP");
    }
}
