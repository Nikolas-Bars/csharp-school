using System;

int hp = 30;
int maxHp = 100;
int heal = 20;

while (hp < maxHp)
{
    hp += heal;
    Console.WriteLine($"Здоровье: {hp}");
}
