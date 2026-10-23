using System;

int[] hits = { 12, 35, 8, 40, 30, 19, 33 };
int critDamage = 30;

int crits = 0;
for (int i = 0; i < hits.Length; i++)
{
    if (hits[i] > critDamage)
    {
        crits++;
    }
}
Console.WriteLine($"Критов: {crits} из {hits.Length}");
