# Метод-вопрос

Метод, который возвращает `bool`, отвечает на вопрос «да или нет». Таким методам дают
имена-вопросы: `IsAlive` — «жив?», `CanBuy` — «может купить?», `HasKey` — «есть ключ?».

```csharp
using System;

int heroHp = 25;
if (IsAlive(heroHp))
{
    Console.WriteLine("Герой ещё в бою!");
}
Console.WriteLine(IsAlive(0));

static bool IsAlive(int hp)
{
    return hp > 0;
}
```

На экране — «Герой ещё в бою!» и `False`.

Сравнение `hp > 0` уже даёт готовый `bool`, поэтому его можно сразу вернуть. А вызов
`IsAlive(heroHp)` можно поставить прямо в условие `if`: он сам становится `true` или `false`.
