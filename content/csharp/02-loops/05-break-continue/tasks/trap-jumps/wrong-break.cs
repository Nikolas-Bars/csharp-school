using System;

int cells = 10;
int steps = 0;

for (int cell = 1; cell <= cells; cell++)
{
    if (cell % 3 == 0)
    {
        break;
    }

    Console.WriteLine($"Шаг на клетку {cell}");
    steps++;
}

Console.WriteLine($"Шагов: {steps}");
