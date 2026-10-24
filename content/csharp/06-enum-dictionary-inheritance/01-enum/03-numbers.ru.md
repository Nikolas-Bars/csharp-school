# Значение по умолчанию и номер

У каждого значения перечисления есть номер — по порядку, с нуля: `Idle` — 0, `Walk` — 1,
`Attack` — 2. Узнать номер помогает `(int)` — приведение типа, знакомое по дробным числам.

Поле-перечисление в классе без значения равно первому значению — тому, у которого номер 0. Так же
у поля `int` по умолчанию 0:

```csharp
using System;

Enemy goblin = new Enemy();
Console.WriteLine(goblin.state);
Console.WriteLine((int)goblin.state);

goblin.state = State.Attack;
Console.WriteLine($"{goblin.state} — номер {(int)goblin.state}");

enum State { Idle, Walk, Attack }

class Enemy
{
    public State state;
}
```

На экране:

```text
Idle
0
Attack — номер 2
```

Поэтому первым в перечислении ставят то, что подходит как начальное состояние: у врага — `Idle`.

Обратно, из числа в перечисление, само не получится: `goblin.state = 2;` — ошибка CS0266. Пиши
значение по имени: `goblin.state = State.Attack;`.
