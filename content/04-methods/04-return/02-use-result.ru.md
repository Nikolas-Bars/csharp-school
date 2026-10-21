# Что делать с ответом

Вызов метода, который возвращает `int`, можно писать везде, где подошло бы число:

```csharp
using System;

Console.WriteLine(CalculateDamage(40, 15));
int doubleHit = CalculateDamage(25, 10) * 2;
Console.WriteLine(doubleHit);
Console.WriteLine($"Урон: {CalculateDamage(12, 2)}");

static int CalculateDamage(int attack, int armor)
{
    return attack - armor;
}
```

На экране — `25`, `30` и `Урон: 10`.

После `return` можно сразу писать выражение: `return attack - armor;` — без лишней
переменной.

А если написать `CalculateDamage(25, 10);` отдельной строкой, ответ потеряется: метод
посчитает, но результат никуда не попадёт.
