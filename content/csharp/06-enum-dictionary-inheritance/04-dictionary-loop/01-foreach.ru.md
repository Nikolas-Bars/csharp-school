# Перебор словаря: foreach

Чтобы вывести весь инвентарь, словарь перебирают циклом `foreach`. На каждом шаге цикл даёт одну
пару — ключ вместе со значением:

```csharp
using System;
using System.Collections.Generic;

Dictionary<string, int> items = new Dictionary<string, int>();
items.Add("зелье", 3);
items.Add("стрела", 20);
items.Add("ключ", 1);

foreach (KeyValuePair<string, int> pair in items)
{
    Console.WriteLine($"{pair.Key}: {pair.Value}");
}
```

На экране:

```text
зелье: 3
стрела: 20
ключ: 1
```

- `KeyValuePair<string, int>` («ки-вэлью-пэр», «пара ключ — значение») — тип одной пары. Типы в
  угловых скобках — те же, что у словаря.
- `pair.Key` — ключ, `pair.Value` — значение.

Пару в цикле можно только читать: `pair.Value = 5;` — ошибка CS0200.

О порядке: словарь не обещает перебирать пары в том порядке, в каком их добавляли. Обычно так и
выходит, но после удаления пары новая может встать на её место. Если порядок важен — не полагайся
на словарь.
