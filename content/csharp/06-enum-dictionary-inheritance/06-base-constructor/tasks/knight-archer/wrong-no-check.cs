using System;

int arrows = 2;

Knight knight = new Knight("Артур");
Archer archer = new Archer("Мира", arrows);
knight.Info();
archer.Info();
archer.Shoot();
archer.Shoot();
archer.Shoot();

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

class Knight : Unit
{
    public Knight(string name) : base(name, 120)
    {
    }
}

class Archer : Unit
{
    private int arrows;

    public Archer(string name, int arrows) : base(name, 70)
    {
        this.arrows = arrows;
    }

    public void Shoot()
    {
        arrows--;
        Console.WriteLine($"{name} стреляет. Стрел: {arrows}");
    }
}
