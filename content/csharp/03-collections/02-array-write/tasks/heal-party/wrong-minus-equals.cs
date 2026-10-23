using System;

int[] partyHp = { 70, 25, 90 };
int heal = 40;

partyHp[1] += heal;
partyHp[2] =- 15;

Console.WriteLine($"Воин: {partyHp[0]}");
Console.WriteLine($"Маг: {partyHp[1]}");
Console.WriteLine($"Лучник: {partyHp[2]}");
