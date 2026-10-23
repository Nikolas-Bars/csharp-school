using System;

int bonus = 5;

Weapon sword = new Weapon();
sword.name = "Меч";
sword.damage = 12;
Console.WriteLine($"{sword.name}: урон {sword.damage}");

sword.damage += bonus;
Console.WriteLine($"{sword.name}: урон {sword.damage}");

class weapon
{
    public string name;
    public int damage;
}
