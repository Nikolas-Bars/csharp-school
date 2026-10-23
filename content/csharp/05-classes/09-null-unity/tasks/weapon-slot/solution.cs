using System;

int swordDamage = 15;

Hero hero = new Hero("Артур");
Console.WriteLine($"{hero.name} бьёт на {hero.GetDamage()}");
hero.Equip(new Weapon("Меч", swordDamage));
Console.WriteLine($"{hero.name} бьёт на {hero.GetDamage()}");

class Weapon
{
    public string name;
    public int damage;

    public Weapon(string name, int damage)
    {
        this.name = name;
        this.damage = damage;
    }
}

class Hero
{
    public string name;
    public Weapon weapon;

    public Hero(string name)
    {
        this.name = name;
    }

    public void Equip(Weapon weapon)
    {
        this.weapon = weapon;
    }

    public int GetDamage()
    {
        if (weapon == null)
        {
            return 1;
        }
        return weapon.damage;
    }
}
