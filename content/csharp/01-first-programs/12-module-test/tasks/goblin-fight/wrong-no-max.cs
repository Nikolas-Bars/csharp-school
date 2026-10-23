using System;

int heroHp = 50;
int heroAttack = 12;
int goblinHp = 30;
int goblinAttack = 9;

goblinHp -= heroAttack;
heroHp -= goblinAttack;
Console.WriteLine($"Гоблин: {goblinHp} HP");
Console.WriteLine($"Герой: {heroHp} HP");

goblinHp -= heroAttack * 2;
Console.WriteLine($"Гоблин: {goblinHp} HP");

if (goblinHp == 0)
{
    Console.WriteLine("Победа!");
}
else
{
    Console.WriteLine("Бой продолжается");
}
