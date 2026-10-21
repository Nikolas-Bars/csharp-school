using System;

int[] partyHp = { 70, 25, 90 };
int heal = 40;

partyHp[2] += heal;
partyHp[3] -= 15;

Console.WriteLine($"Воин: {partyHp[0]}");
Console.WriteLine($"Маг: {partyHp[1]}");
Console.WriteLine($"Лучник: {partyHp[2]}");
