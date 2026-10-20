# Не

`!` — «не»: переворачивает `bool`. `!true` — это `false`, `!false` — это `true`.

```csharp
using System;

bool isAlive = false;
if (!isAlive)
{
    Console.WriteLine("Игра окончена.");
}
```

`if (!isAlive)` читается как «если не жив».

Операторы можно сочетать: `hp > 0 && !isPoisoned` — «жив и не отравлен».
