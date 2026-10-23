using System;

int potion = 25;

Hero knight = new Hero();
knight.name = "Рыцарь";
knight.hp = 40;

Hero mage = new Hero();
mage.name = "Маг";
mage.hp = 30;

Heal(knight, potion);
Heal(mage, potion * 2);
Console.WriteLine($"{knight.name}: {knight.hp} HP");
Console.WriteLine($"{mage.name}: {mage.hp} HP");

class Hero
{
    public string name;
    public int hp;
}

static void Heal(Hero target, int amount)
{
    target.hp += amount;
}
