using System;

int heroHp = 50;
int heroAttack = 12;
int goblinHp = 30;
int goblinAttack = 9;

goblinHp = Math.Max(goblinHp - heroAttack * 3, 0);

if (goblinHp = 0)
{
    Console.WriteLine("Победа!");
}
