# Ранний выход

`return` не обязательно стоит в конце. Как только метод доходит до `return`, он сразу
заканчивается, и команды ниже уже не выполняются. Это называют **ранним выходом**:

```csharp
using System;

Console.WriteLine(GetTitle(12));
Console.WriteLine(GetTitle(6));
Console.WriteLine(GetTitle(1));

static string GetTitle(int level)
{
    if (level >= 10)
    {
        return "Мастер";
    }
    if (level >= 5)
    {
        return "Воин";
    }
    return "Новичок";
}
```

На экране:

```text
Мастер
Воин
Новичок
```

При уровне 12 метод отвечает «Мастер» на первой же проверке — до второго `if` дело не
доходит. Поэтому `else` здесь не нужен: если сработал `return`, дальше метод не пойдёт.
