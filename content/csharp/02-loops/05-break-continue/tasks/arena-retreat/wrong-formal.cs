using System;

int hp = 50;
int damage = 12;

for (int wave = 1; wave <= 10; wave++)
{
    if (hp < 15)
    {
        break;
    }
}

Console.WriteLine("Волна 1: здоровье 38");
Console.WriteLine("Волна 2: здоровье 26");
Console.WriteLine("Волна 3: здоровье 14");
Console.WriteLine("Пора отступать!");
