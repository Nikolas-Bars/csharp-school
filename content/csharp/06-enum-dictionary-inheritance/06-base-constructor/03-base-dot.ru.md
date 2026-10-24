# base. — обращение к базовому классу

Слово `base` работает не только у конструктора. `base.Info()` внутри метода наследника вызывает
метод `Info` базового класса:

```csharp
using System;

Archer archer = new Archer("Мира", 70, 12);
archer.FullInfo();

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

    public void FullInfo()
    {
        base.Info();
        Console.WriteLine($"Стрел: {arrows}");
    }
}
```

На экране:

```text
Мира: 70 HP
Стрел: 12
```

Пока у лучника нет своего метода `Info`, `base.Info()` и просто `Info()` делают одно и то же.
Разница появится в следующем уроке: у наследника будет своя версия метода с тем же именем, и
`base.` поможет вызвать версию базового класса.
