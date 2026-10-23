using System;

int arrows = 5;

while (arrows >= 0)
{
    arrows--;
    Console.WriteLine($"Выстрел! Осталось стрел: {arrows}");
}

Console.WriteLine("Стрелы кончились");
