# Объект отвечает

Метод объекта может и отвечать — так же, как методы из модуля «Методы»: вместо `void` пишут
тип ответа, а ответ отдают через `return`.

```csharp
using System;

Enemy goblin = new Enemy();
goblin.hp = 30;
goblin.armor = 5;

Console.WriteLine(goblin.IsAlive());
Console.WriteLine(goblin.DamageFrom(12));

class Enemy
{
    public int hp;
    public int armor;

    public bool IsAlive()
    {
        return hp > 0;
    }

    public int DamageFrom(int attack)
    {
        return Math.Max(attack - armor, 0);
    }
}
```

На экране:

```text
True
7
```

Разница с прошлым модулем одна: ответ считается из полей объекта. `IsAlive` не получает
здоровье параметром — он смотрит в своё поле `hp`. А `DamageFrom` берёт атаку из параметра, а
броню — из поля: сколько урона пройдёт сквозь броню именно этого врага.
