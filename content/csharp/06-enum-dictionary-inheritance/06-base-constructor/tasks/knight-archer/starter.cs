using System;

int arrows = 2;

// Создай рыцаря и лучницу, выведи их и выстрели три раза

class Unit
{
    public string name;
    public int hp;

    public Unit(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }

    public void Info()
    {
        Console.WriteLine($"{name}: {hp} HP");
    }
}

// Ниже объяви наследников Knight и Archer
