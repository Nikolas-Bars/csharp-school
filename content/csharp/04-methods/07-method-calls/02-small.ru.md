# Маленькие методы

Большую задачу удобно делить на маленькие методы. Каждый делает что-то одно, и у каждого
понятное имя:

```csharp
using System;

PlayTurn(30);
PlayTurn(0);

static void PlayTurn(int hp)
{
    if (IsAlive(hp))
    {
        Attack();
    }
    else
    {
        Console.WriteLine("Герой повержен");
    }
}

static bool IsAlive(int hp)
{
    return hp > 0;
}

static void Attack()
{
    Console.WriteLine("Герой атакует!");
}
```

На экране — «Герой атакует!» и «Герой повержен».

`PlayTurn` читается почти как текст: «если жив — атакуй». Чтобы понять ход, не нужно вникать,
как устроены `IsAlive` и `Attack`. Так написаны и скрипты в Unity: много небольших методов с
говорящими именами.
