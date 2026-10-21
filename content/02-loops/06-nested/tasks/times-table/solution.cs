using System;

int size = 5;

for (int row = 1; row <= size; row++)
{
    for (int col = 1; col <= size; col++)
    {
        Console.Write($"{row * col} ");
    }
    Console.WriteLine();
}
