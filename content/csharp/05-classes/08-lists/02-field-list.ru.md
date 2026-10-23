# Список внутри объекта

Поле класса тоже может быть списком. Так устроен инвентарь: объект `Inventory` хранит вещи в
своём `private`-списке, а наружу даёт методы:

```csharp
using System;
using System.Collections.Generic;

Inventory bag = new Inventory();
bag.Add("меч");
bag.Add("зелье");
bag.Show();

class Inventory
{
    private List<string> items;

    public Inventory()
    {
        items = new List<string>();
    }

    public void Add(string item)
    {
        items.Add(item);
    }

    public void Show()
    {
        Console.WriteLine($"Вещей: {items.Count}");
        foreach (string item in items)
        {
            Console.WriteLine($"- {item}");
        }
    }
}
```

На экране:

```text
Вещей: 2
- меч
- зелье
```

Сам список конструктор создаёт через `new List<string>()`. Если это забыть, в поле `items`
останется `null` — значение по умолчанию, — и первый же `items.Add` остановит программу.

Конструктор может быть и без параметров, как здесь: он просто готовит объект к работе.
