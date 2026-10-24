# switch по перечислению

Перечисление и `switch` хорошо подходят друг другу: значений у переменной всего несколько, и на
каждое — свой вариант `case`:

```csharp
using System;

State state = State.Attack;

switch (state)
{
    case State.Idle:
        Console.WriteLine("Враг стоит на месте");
        break;
    case State.Walk:
        Console.WriteLine("Враг идёт по дороге");
        break;
    case State.Attack:
        Console.WriteLine("Враг атакует!");
        break;
}

enum State { Idle, Walk, Attack }
```

На экране:

```text
Враг атакует!
```

В `case` значение пишут полностью, с именем типа: `case State.Attack:`. Просто `case Attack:` не
сработает — ошибка CS0103: имени `Attack` самого по себе нет, оно живёт внутри `State`.

`break;` в конце каждого варианта обязателен, как в любом `switch`. А `default:` можно добавить
для всех остальных значений.
