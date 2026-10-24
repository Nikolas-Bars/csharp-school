# Свои параметры наследника

У наследника могут быть свои поля, и их тоже задают в конструкторе. Часть параметров уходит в
`base(…)`, остальные наследник кладёт в свои поля:

```csharp
using System;

Archer archer = new Archer("Мира", 70, 12);
archer.Info();
Console.WriteLine($"Стрел: {archer.arrows}");

class Unit
{
    public string name;
    public int hp;

    public Unit(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }

    public void Info()
    {
        Console.WriteLine($"{name}: {hp} HP");
    }
}

class Archer : Unit
{
    public int arrows;

    public Archer(string name, int hp, int arrows) : base(name, hp)
    {
        this.arrows = arrows;
    }
}
```

На экране:

```text
Мира: 70 HP
Стрел: 12
```

`name` и `hp` уходят в конструктор `Unit` (боец), а `arrows` (стрелы) лучник кладёт в своё поле.
Порядок тот же: сначала `base(name, hp)` заполняет имя и здоровье, потом тело конструктора
`Archer` — стрелы.
