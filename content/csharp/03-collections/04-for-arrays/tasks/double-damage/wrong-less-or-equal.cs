using System;

int[] damage = { 12, 7, 20, 15, 9 };
int power = 2;

for (int i = 0; i <= damage.Length; i++)
{
    damage[i] *= power;
    Console.WriteLine(damage[i]);
}

int total = 0;
for (int i = 0; i < damage.Length; i++)
{
    total += damage[i];
}
Console.WriteLine($"Сумма после зелья: {total}");
