# Среднее: (double)a / b

Помнишь: у двух `int` деление целое, `25 / 4` — это 6. Чтобы честно посчитать среднее, одно
из чисел до деления превращают в `double`:

```csharp
using System;

int totalDamage = 25;
int hits = 4;
double average = (double)totalDamage / hits;
Console.WriteLine(average);
```

Напечатается `6.25`. Приведение выполняется раньше деления: `totalDamage` становится 25.0, и
деление уже дробное.

А `(double)(totalDamage / hits)` даст 6. В скобках сначала делятся два `int` — получается 6, —
и только потом готовое 6 превращается в `double`. Что в скобках, то и выполняется раньше.
