using System;

int level = 12;
int hp = 100;
int maxHp = 100;
int bossLevel = 10;

Console.WriteLine($"Уровень выше босса: {level > bossLevel}");
Console.WriteLine($"Здоровье полное: {hp == maxHp}");
Console.WriteLine($"Уровни равны: {level == bossLevel}");
