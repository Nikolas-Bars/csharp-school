# Есть ли такой ключ: ContainsKey

Чтобы не наткнуться на `KeyNotFoundException`, сначала спроси словарь, есть ли в нём ключ. Метод
`ContainsKey` («содержит ключ») отвечает `true` или `false`:

```csharp
using System;
using System.Collections.Generic;

Dictionary<string, int> items = new Dictionary<string, int>();
items.Add("зелье", 3);

string wanted = "щит";
if (items.ContainsKey(wanted))
{
    Console.WriteLine($"{wanted}: {items[wanted]}");
}
else
{
    Console.WriteLine($"{wanted}: нет в инвентаре");
}
```

На экране:

```text
щит: нет в инвентаре
```

ContainsKey выручает и перед `Add`: если ключа ещё нет — добавляем пару, если есть — меняем
значение.
