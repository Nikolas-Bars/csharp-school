using System;

int armor = 5;
int hit = 12;

// Создай Чёрного рыцаря, ударь его два раза и выведи после каждого удара

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

// Ниже объяви наследника ArmoredEnemy
