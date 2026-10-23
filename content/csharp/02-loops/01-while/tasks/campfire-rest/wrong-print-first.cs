using System;

int hp = 30;
int maxHp = 100;
int heal = 20;

while (hp < maxHp)
{
    Console.WriteLine($"Здоровье: {hp}");
    hp = Math.Min(hp + heal, maxHp);
}
