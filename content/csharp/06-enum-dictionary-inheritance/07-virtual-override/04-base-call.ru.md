# Своё плюс общее: base.Attack()

Иногда наследник не заменяет атаку целиком, а добавляет к ней своё. Тогда внутри `override`
вызывают версию базового класса — `base.Attack()`:

```csharp
using System;

Berserker berserker = new Berserker("Бьорн");
berserker.Attack();

class Enemy
{
    public string name;

    public Enemy(string name)
    {
        this.name = name;
    }

    public virtual void Attack()
    {
        Console.WriteLine($"{name} бьёт: 5 урона");
    }
}

class Berserker : Enemy
{
    public Berserker(string name) : base(name)
    {
    }

    public override void Attack()
    {
        base.Attack();
        Console.WriteLine($"{name} в ярости бьёт ещё раз!");
        base.Attack();
    }
}
```

На экране:

```text
Бьорн бьёт: 5 урона
Бьорн в ярости бьёт ещё раз!
Бьорн бьёт: 5 урона
```

`base.Attack()` — атака из `Enemy`. Код удара не копируется: поменяешь урон в `Enemy` — берсерк
тоже станет бить по-новому.

Без `base.` здесь нельзя: просто `Attack()` внутри `Attack` вызовет сам себя, потом снова и снова,
пока программа не упадёт.
