using System;

int[] anyaHits = { 12, 31, 7, 25 };
int[] borisHits = { 18, 4, 9 };

Console.WriteLine($"Аня: {FindMax(anyaHits)}");
Console.WriteLine($"Борис: {FindMax(borisHits)}");

static int FindMax(int[] hits)
{
    return hits[0];
}
