using System;

int upgrades = 2;

Weapon sword = new Weapon();
sword.name = "Меч";
sword.damage = 10;
sword.level = upgrades;
string text = sword.Describe();
Console.WriteLine(text);

class Weapon
{
    public string name;
    public int damage;
    public int level;

    public int GetDamage()
    {
        return (damage + level) * 5;
    }

    public string Describe()
    {
        return $"{name} +{level}: урон {GetDamage()}";
    }
}
