using System;

int[] anyaHits = { 12, 31, 7, 25 };
int[] borisHits = { 18, 4, 9 };

FindMax(anyaHits);
Console.WriteLine("Аня: 31");
Console.WriteLine("Борис: 18");

static int FindMax(int[] hits)
{
    int max = hits[0];
    foreach (int hit in hits)
    {
        if (hit > max)
        {
            max = hit;
        }
    }
    return max;
}
