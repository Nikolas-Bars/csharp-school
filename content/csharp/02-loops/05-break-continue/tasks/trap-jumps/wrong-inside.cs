using System;

int cells = 10;

for (int cell = 1; cell <= cells; cell++)
{
    int steps = 0;
    if (cell % 3 == 0)
    {
        continue;
    }

    Console.WriteLine($"Шаг на клетку {cell}");
    steps++;
}

Console.WriteLine($"Шагов: {steps}");
