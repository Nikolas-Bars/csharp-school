using System;

int bossHp = 100;

Fighter knight = new Fighter("Рыцарь", 80, 30);
Fighter dragon = new Fighter("Дракон", bossHp, 25);

while (knight.IsAlive() && dragon.IsAlive())
{
    knight.Hit(dragon);
    dragon.Hit(knight);
}

if (knight.IsAlive())
{
    Console.WriteLine($"Победа: {knight.Name}");
}
else
{
    Console.WriteLine($"Победа: {dragon.Name}");
}

class Fighter
{
    public string Name { get; private set; }
    public int Hp { get; private set; }
    public int Attack { get; private set; }

    public Fighter(string name, int hp, int attack)
    {
        Name = name;
        Hp = hp;
        Attack = attack;
    }

    public void TakeDamage(int damage)
    {
        Hp = Math.Max(Hp - damage, 0);
    }

    public bool IsAlive()
    {
        return Hp > 0;
    }

    public void Hit(Fighter target)
    {
        target.TakeDamage(Attack);
        Console.WriteLine($"{Name} -> {target.Name}: {target.Hp} HP");
    }
}
