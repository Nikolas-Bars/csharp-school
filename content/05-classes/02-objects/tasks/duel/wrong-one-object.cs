using System;

int knightAttack = 15;

Hero knight = new Hero();
knight.name = "Рыцарь";
knight.hp = 100;
knight.attack = knightAttack;

Hero orc = knight;
orc.name = "Орк";
orc.hp = 90;
orc.attack = 20;

orc.hp -= knight.attack;
knight.hp -= orc.attack;

Console.WriteLine($"{knight.name}: {knight.hp} HP");
Console.WriteLine($"{orc.name}: {orc.hp} HP");

class Hero
{
    public string name;
    public int hp;
    public int attack;
}
