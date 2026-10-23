# return в цикле

`return` внутри цикла сразу заканчивает и цикл, и весь метод. Это удобно для поиска: нашли —
сразу отвечаем, дальше можно не смотреть.

```csharp
using System;

string[] bag = { "меч", "зелье", "ключ" };
Console.WriteLine(HasItem(bag, "зелье"));
Console.WriteLine(HasItem(bag, "щит"));

static bool HasItem(string[] items, string name)
{
    foreach (string item in items)
    {
        if (item == name)
        {
            return true;
        }
    }
    return false;
}
```

На экране — `True` и `False`.

`return false;` стоит **после** цикла: сказать «нет такой вещи» можно, только проверив все.
Если поставить его внутрь цикла, в `else`, метод ответит `false` уже на первой неподходящей
вещи.

Компилятор и сам требует `return` после цикла: вдруг в массиве нет ни одного элемента? Тогда
цикл не выполнится ни разу, и метод останется без ответа — ошибка CS0161.
