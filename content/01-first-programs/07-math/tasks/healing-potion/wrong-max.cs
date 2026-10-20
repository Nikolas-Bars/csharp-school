using System;

int hp = 80;
int maxHp = 100;
int heal = 35;

hp = Math.Max(hp + heal, maxHp);
Console.WriteLine(hp);
