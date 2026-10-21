# Метод зовёт метод

Внутри класса свои методы зовут просто по имени, без объекта и точки — как и поля:

```csharp
using System;

Hero hero = new Hero();
hero.name = "Мира";
hero.hp = 0;
hero.Attack();

hero.hp = 40;
hero.Attack();

class Hero
{
    public string name;
    public int hp;

    public void Attack()
    {
        if (IsAlive())
        {
            Console.WriteLine($"{name} атакует!");
        }
        else
        {
            Console.WriteLine($"{name} не может атаковать");
        }
    }

    public bool IsAlive()
    {
        return hp > 0;
    }
}
```

На экране:

```text
Мира не может атаковать
Мира атакует!
```

`IsAlive()` внутри `Attack` значит «спроси у этого же героя». Писать `hero.IsAlive()` внутри
класса нельзя: переменная `hero` живёт в основном коде, а внутри класса её использовать нельзя
(ошибка CS8801).

А объект, пришедший в параметре, — можно:
`public void Hit(Hero target) { target.TakeDamage(attack); }`. Нельзя только переменные основного
кода.

Порядок методов в классе не важен: `Attack` вызывает `IsAlive`, хотя тот объявлен ниже.
