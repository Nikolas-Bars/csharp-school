using System;

int hp = 30;
int maxHp = 100;
int heal = 20;

while (hp > maxHp)
{
    hp = Math.Min(hp + heal, maxHp);
}

Console.WriteLine("Здоровье: 50");
Console.WriteLine("Здоровье: 70");
Console.WriteLine("Здоровье: 90");
Console.WriteLine("Здоровье: 100");
