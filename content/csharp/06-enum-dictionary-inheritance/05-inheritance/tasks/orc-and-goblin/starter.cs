using System;

int hit = 10;
int orcRage = 3;

// Создай орка и гоблина, ударь обоих и выведи

class Enemy
{
    public string name;
    public int hp;

    public void TakeDamage(int damage)
    {
        hp = Math.Max(hp - damage, 0);
    }

    public void Info()
    {
        Console.WriteLine($"{name}: {hp} HP");
    }
}

// Ниже объяви наследников Orc и Goblin
