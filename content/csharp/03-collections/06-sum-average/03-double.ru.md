# Дробное среднее

Деление получается дробным, если хотя бы одно из чисел дробное — как `7.0 / 2` в первом модуле.
Проще всего сделать дробной саму сумму:

```csharp
using System;

int[] damage = { 12, 15, 10, 14 };
double total = 0;
foreach (int d in damage)
{
    total += d;
}
double average = total / damage.Length;
Console.WriteLine(average);
```

На экране:

```text
12.75
```

Целое число в `double` положить можно — ничего не теряется. Теперь `total` дробная, и
`total / damage.Length` даёт 12.75.

Запомни: считаешь среднее — сумма типа `double`.
