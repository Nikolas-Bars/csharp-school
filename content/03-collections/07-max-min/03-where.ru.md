# Где он стоит

Иногда нужно знать не только сам минимум, но и где он: какого врага бить. Тогда вместе со
значением запоминают индекс, а перебирают через `for`:

```csharp
using System;

int[] enemyHp = { 70, 45, 90, 30, 60 };
int weakest = enemyHp[0];
int weakestIndex = 0;
for (int i = 0; i < enemyHp.Length; i++)
{
    if (enemyHp[i] < weakest)
    {
        weakest = enemyHp[i];
        weakestIndex = i;
    }
}
Console.WriteLine($"Слабее всех враг с индексом {weakestIndex}: {weakest} HP");
```

На экране:

```text
Слабее всех враг с индексом 3: 30 HP
```

Обе переменные обновляются вместе, в одном блоке `if`.
