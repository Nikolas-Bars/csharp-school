# Удаление: сначала собрать, потом удалить

`Remove(ключ)` удаляет пару из словаря. Если такого ключа нет, ничего не случится.

Правило из списков действует и для словаря: внутри `foreach` по словарю пары не удаляют и не
добавляют. В Unity это остановит программу с `InvalidOperationException`. В браузере .NET новее и
может не упасть, но в Unity упадёт — поэтому так не пишут. Делают в два шага: в цикле собирают
ключи в список, а после цикла удаляют их:

```csharp
using System;
using System.Collections.Generic;

Dictionary<string, int> enemies = new Dictionary<string, int>();
enemies.Add("гоблин", 0);
enemies.Add("орк", 25);
enemies.Add("волк", 0);

List<string> defeated = new List<string>();
foreach (KeyValuePair<string, int> pair in enemies)
{
    if (pair.Value == 0)
    {
        defeated.Add(pair.Key);
    }
}

foreach (string name in defeated)
{
    enemies.Remove(name);
}

Console.WriteLine($"Осталось врагов: {enemies.Count}");
```

На экране:

```text
Осталось врагов: 1
```

Здесь словарь хранит здоровье врагов: ключ — имя, значение — HP. Побеждённые сначала попадают в
список `defeated`, а удаляются вторым циклом — по списку, а не по словарю.
