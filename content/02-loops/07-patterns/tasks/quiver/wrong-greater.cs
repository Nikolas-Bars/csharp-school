using System;

int arrows = 5;

for (int left = arrows; left > 1; left--)
{
    for (int i = 1; i <= left; i++)
    {
        Console.Write(">");
    }
    Console.WriteLine();
}
