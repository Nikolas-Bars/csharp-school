# Переменная-перечисление

Объявленное перечисление — новый тип, как `int` или твой класс `Hero`. Переменная этого типа
хранит одно из его значений:

```csharp
using System;

int distance = 3;
State state = State.Idle;
Console.WriteLine(state);

if (distance < 5)
{
    state = State.Attack;
}

if (state == State.Attack)
{
    Console.WriteLine("Враг атакует!");
}
Console.WriteLine($"Состояние: {state}");

enum State { Idle, Walk, Attack }
```

На экране:

```text
Idle
Враг атакует!
Состояние: Attack
```

- Новое значение кладут, как в любую переменную: `state = State.Attack;`.
- Сравнивают через `==` и `!=`, как числа.
- `Console.WriteLine(state)` печатает **имя** значения — `Attack`, а не номер. В `$"…"` так же.

Перечисление, как и класс, пишут в конце файла — после всех команд. Если поставить его выше
команд, будет ошибка CS8803.
