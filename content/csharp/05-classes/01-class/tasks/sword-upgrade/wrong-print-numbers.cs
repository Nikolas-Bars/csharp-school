using System;

int bonus = 5;

Weapon sword = new Weapon();
sword.name = "Меч";
sword.damage = 12;
Console.WriteLine($"{sword.name}: урон 12");

sword.damage += bonus;
Console.WriteLine($"{sword.name}: урон {12 + bonus}");

class Weapon
{
    public string name;
    public int damage;
}
