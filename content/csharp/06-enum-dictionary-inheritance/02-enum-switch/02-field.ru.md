# Состояние внутри объекта

В играх состояние хранят в самом враге — полем. Методы врага меняют это поле и смотрят на него:

```csharp
using System;

Enemy wolf = new Enemy();
wolf.name = "Волк";
wolf.Act();
wolf.SeeHero();
wolf.Act();

enum State { Idle, Walk, Attack }

class Enemy
{
    public string name;
    public State state;

    public void SeeHero()
    {
        state = State.Attack;
    }

    public void Act()
    {
        switch (state)
        {
            case State.Idle:
                Console.WriteLine($"{name} спит");
                break;
            case State.Walk:
                Console.WriteLine($"{name} бродит");
                break;
            case State.Attack:
                Console.WriteLine($"{name} кусает!");
                break;
        }
    }
}
```

На экране:

```text
Волк спит
Волк кусает!
```

- `state` — поле: у каждого волка своё состояние. Сначала оно `Idle` — первое значение.
- `SeeHero` («увидеть героя») меняет состояние, а `Act` («действовать») по нему решает, что
  делать.

Так часто устроены враги в Unity: метод `Update` каждый кадр делает что-то вроде `Act`, а события
игры — заметил героя, получил урон — меняют состояние. Такую схему называют **конечным
автоматом**: у объекта несколько состояний, и в каждый момент он ровно в одном из них.
