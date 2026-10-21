using System;

int[] wave = { 40, 85, 50, 120, 55 };
int minHp = 50;

foreach (int hp in wave)
{
    if (hp > minHp)
    {
        Console.WriteLine($"Сильный враг: {wave[hp]}");
    }
}
