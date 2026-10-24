# Враг — это любой наследник

Орк — это враг. Поэтому переменная типа `Enemy` может хранить орка:

```csharp
using System;

Enemy enemy = new Orc();
enemy.name = "Грох";
enemy.TakeDamage(5);
Console.WriteLine($"{enemy.name}: {enemy.hp} HP");

class Enemy
{
    public string name;
    public int hp = 30;

    public void TakeDamage(int damage)
    {
        hp = Math.Max(hp - damage, 0);
    }
}

class Orc : Enemy
{
    public void Roar()
    {
        Console.WriteLine($"{name} рычит!");
    }
}
```

На экране:

```text
Грох: 25 HP
```

Через переменную типа `Enemy` видно только то, что есть у любого врага: `name`, `hp`,
`TakeDamage`. Объект в ней — настоящий орк, но `enemy.Roar()` — ошибка CS1061: рык есть не у
каждого врага.

Наоборот нельзя: `Orc orc = new Enemy();` — ошибка CS0266, ведь не каждый враг — орк.

Это пригодится совсем скоро: в один список `List<Enemy>` можно сложить и орков, и гоблинов.
