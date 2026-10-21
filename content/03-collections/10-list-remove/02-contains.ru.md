# Contains — есть ли?

У списка искать флагом в цикле не нужно: есть метод `Contains` («содержит»). Он возвращает `bool`:
`true`, если элемент есть, и `false`, если нет.

```csharp
using System;
using System.Collections.Generic;

List<string> bag = new List<string> { "факел", "ключ" };
if (bag.Contains("ключ"))
{
    Console.WriteLine("Дверь открыта.");
    bag.Remove("ключ");
}
Console.WriteLine($"Предметов: {bag.Count}");
```

На экране:

```text
Дверь открыта.
Предметов: 1
```

`Contains` сравнивает буква в букву: `"Ключ"` и `"ключ"` для него разные строки.

У массива своего `Contains` нет — пока ищи циклом с флагом.
