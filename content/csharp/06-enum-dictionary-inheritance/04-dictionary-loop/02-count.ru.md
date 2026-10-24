# Подсчёт словарём

Словарь хорошо считает, сколько раз что встречается. Ключ — что считаем, значение — сколько раз
уже видели:

```csharp
using System;
using System.Collections.Generic;

string[] kills = { "гоблин", "волк", "гоблин", "гоблин" };
Dictionary<string, int> counts = new Dictionary<string, int>();

foreach (string enemy in kills)
{
    if (counts.ContainsKey(enemy))
    {
        counts[enemy]++;
    }
    else
    {
        counts.Add(enemy, 1);
    }
}

Console.WriteLine($"Гоблинов: {counts["гоблин"]}");
Console.WriteLine($"Волков: {counts["волк"]}");
```

На экране:

```text
Гоблинов: 3
Волков: 1
```

Встретили врага впервые — добавляем его со счётом 1. Уже видели — прибавляем единицу к его
значению.
