using System;

int chests = 6;
int total;

for (int chest = 1; chest <= chests; chest++)
{
    total += chest * 10;
}

Console.WriteLine($"Всего золота: {total}");
