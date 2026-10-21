using System;

int durability = 2;

Weapon axe = new Weapon("Топор", 18, durability);
for (int i = 0; i < 3; i++)
{
    axe.Use();
}

class Weapon
{
    public string name;
    public int damage;
    public int durability;

    public Weapon(string weaponName, int weaponDamage, int weaponDurability)
    {
        name = weaponName;
        damage = weaponDamage;
        durability = weaponDurability;
    }

    public void Use()
    {
        if (durability > 0)
        {
            durability--;
            Console.WriteLine($"{name} бьёт на {damage}");
        }
        else
        {
            Console.WriteLine($"{name} сломан");
        }
    }
}
