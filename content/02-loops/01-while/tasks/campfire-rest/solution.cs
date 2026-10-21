using System;

int hp = 30;
int maxHp = 100;
int heal = 20;

while (hp < maxHp)
{
    hp = Math.Min(hp + heal, maxHp);
    Console.WriteLine($"Здоровье: {hp}");
}
