# Спросить и сразу получить: TryGetValue

ContainsKey и чтение по ключу — два обращения к словарю. `TryGetValue` («попробуй получить
значение») делает оба дела сразу:

```csharp
using System;
using System.Collections.Generic;

Dictionary<string, int> prices = new Dictionary<string, int>();
prices.Add("меч", 50);

if (prices.TryGetValue("меч", out int price))
{
    Console.WriteLine($"Меч стоит {price}");
}

if (!prices.TryGetValue("лук", out int bowPrice))
{
    Console.WriteLine("Лука в продаже нет");
}
```

На экране:

```text
Меч стоит 50
Лука в продаже нет
```

- Метод отвечает `true`, если ключ есть, и `false`, если нет, — поэтому его ставят прямо в `if`.
- `out int price` — новое слово `out` («аут», «наружу»). Обычно значение передают в метод, а через
  `out`-параметр метод **отдаёт** значение: кладёт найденную цену в переменную `price`. Переменную
  объявляют прямо в скобках вызова.
- Если ключа нет, в переменной окажется значение по умолчанию — 0. Поэтому её берут внутри `if`,
  где ответ `true`.
