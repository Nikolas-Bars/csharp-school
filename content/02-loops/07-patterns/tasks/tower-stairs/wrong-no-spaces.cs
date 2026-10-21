using System;

int height = 4;

for (int row = 1; row <= height; row++)
{
    for (int block = 1; block <= row; block++)
    {
        Console.Write("#");
    }
    Console.WriteLine();
}
