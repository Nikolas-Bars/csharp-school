using System;

int heroHp = 50;
int heroAttack = 12;
int goblinHp = 30;
int goblinAttack = 9;

goblinHp = Math.Max(goblinHp - heroAttack * 3, 0);
Console.WriteLine("Гоблин: 18 HP");
Console.WriteLine("Герой: 41 HP");
Console.WriteLine("Гоблин: 0 HP");

if (goblinHp == 0)
{
    Console.WriteLine("Победа!");
}
else
{
    Console.WriteLine("Бой продолжается");
}
