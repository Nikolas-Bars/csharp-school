using System;

int[] wave = { 20, 35, 25, 150 };
int rage = 2;

wave[wave.Length] *= rage;

Console.WriteLine($"Врагов в волне: {wave.Length}");
Console.WriteLine($"Здоровье босса: {wave[wave.Length]}");
