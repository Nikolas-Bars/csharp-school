# Читать можно, менять — нет

Самое полезное: у `set` можно поставить свой модификатор доступа. `{ get; private set; }` —
прочитать свойство может кто угодно, а поменять — только методы самого класса:

```csharp
using System;

Hero hero = new Hero("Артур", 100);
hero.TakeDamage(30);
Console.WriteLine($"{hero.Name}: {hero.Hp} HP");

class Hero
{
    public string Name { get; private set; }
    public int Hp { get; private set; }

    public Hero(string name, int hp)
    {
        Name = name;
        Hp = hp;
    }

    public void TakeDamage(int damage)
    {
        Hp = Math.Max(Hp - damage, 0);
    }
}
```

На экране:

```text
Артур: 70 HP
```

Это короче, чем `private`-поле плюс метод `GetHp`, а защита та же. Запись `hero.Hp = 500;` в
основном коде — ошибка CS0272: менять `Hp` снаружи нельзя.

Здесь не нужен даже `this`: свойство `Hp` с большой буквы, параметр `hp` — с маленькой, имена
разные.
