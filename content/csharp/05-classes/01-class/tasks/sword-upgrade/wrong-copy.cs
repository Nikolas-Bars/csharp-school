using System;

int bonus = 5;

Weapon sword = new Weapon();
sword.name = "Меч";
sword.damage = 12;
Console.WriteLine($"{sword.name}: урон {sword.damage}");

int damage = sword.damage;
damage += bonus;
Console.WriteLine($"{sword.name}: урон {sword.damage}");

class Weapon
{
    public string name;
    public int damage;
}
