# Массив в параметре

Параметр может быть массивом. Его тип пишут так же, как при объявлении массива, — `int[]`:

```csharp
using System;

int[] goblinHits = { 5, 8, 12 };
int[] orcHits = { 20, 15 };
Console.WriteLine(Sum(goblinHits));
Console.WriteLine(Sum(orcHits));

static int Sum(int[] numbers)
{
    int total = 0;
    foreach (int number in numbers)
    {
        total += number;
    }
    return total;
}
```

На экране — `25` и `35`.

Методу всё равно, сколько в массиве элементов: он перебирает все, сколько есть. `Sum` пишут
один раз, а считают им сумму любого массива.
