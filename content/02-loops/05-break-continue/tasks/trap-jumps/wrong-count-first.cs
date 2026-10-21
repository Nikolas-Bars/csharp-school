using System;

int cells = 10;
int steps = 0;

for (int cell = 1; cell <= cells; cell++)
{
    steps++;
    if (cell % 3 == 0)
    {
        continue;
    }

    Console.WriteLine($"Шаг на клетку {cell}");
}

Console.WriteLine($"Шагов: {steps}");
