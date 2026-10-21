using System;

int hp = 50;
int damage = 12;

for (int wave = 1; wave <= 10; wave++)
{
    hp -= damage;
    Console.WriteLine($"Волна {wave}: здоровье {hp}");

    if (hp < 15)
    {
        Console.WriteLine("Пора отступать!");
        continue;
    }
}
