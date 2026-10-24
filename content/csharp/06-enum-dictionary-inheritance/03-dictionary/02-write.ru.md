# Запись по ключу

Через квадратные скобки значение можно и поменять — как элемент массива:

```csharp
using System;
using System.Collections.Generic;

Dictionary<string, int> items = new Dictionary<string, int>();
items.Add("зелье", 3);

items["зелье"] = 5;
items["зелье"] -= 1;
items["кольцо"] = 1;

Console.WriteLine(items["зелье"]);
Console.WriteLine(items["кольцо"]);
Console.WriteLine(items.Count);
```

На экране:

```text
4
1
2
```

- `items["зелье"] = 5;` — у зелья теперь 5. `+=`, `-=` и `++` тоже работают.
- `items["кольцо"] = 1;` — кольца в словаре не было, и запись по ключу **добавила** новую пару.

Две ошибки, на которых программа остановится:

- `Add` с ключом, который уже есть, — `ArgumentException` («неверный аргумент»). Хочешь поменять
  значение — пиши через `[ключ]`.
- Чтение по ключу, которого нет, — например, `items["щит"]` — `KeyNotFoundException` («ключ не
  найден»).
