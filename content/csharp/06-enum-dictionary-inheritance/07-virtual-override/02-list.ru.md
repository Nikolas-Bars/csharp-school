# Список разных врагов

Самое полезное начинается в списке `List<Enemy>`. В него можно сложить любых наследников врага, и
у каждого сработает **его** версия метода:

```csharp
using System;
using System.Collections.Generic;

List<Enemy> wave = new List<Enemy>();
wave.Add(new Enemy("Крыса"));
wave.Add(new Orc("Грох"));
wave.Add(new Goblin("Скрип"));

foreach (Enemy enemy in wave)
{
    enemy.Attack();
}

class Enemy
{
    public string name;

    public Enemy(string name)
    {
        this.name = name;
    }

    public virtual void Attack()
    {
        Console.WriteLine($"{name} кусает: 2 урона");
    }
}

class Orc : Enemy
{
    public Orc(string name) : base(name)
    {
    }

    public override void Attack()
    {
        Console.WriteLine($"{name} бьёт дубиной: 12 урона");
    }
}

class Goblin : Enemy
{
    public Goblin(string name) : base(name)
    {
    }

    public override void Attack()
    {
        Console.WriteLine($"{name} кидает камень: 4 урона");
    }
}
```

На экране:

```text
Крыса кусает: 2 урона
Грох бьёт дубиной: 12 урона
Скрип кидает камень: 4 урона
```

Переменная `enemy` в цикле — типа `Enemy`, но C# смотрит, какой объект в ней лежит на самом деле:
у орка вызывается атака орка, у гоблина — гоблина. Это называют **полиморфизмом** («много форм»):
вызов один — `enemy.Attack()`, а действий столько, сколько видов врагов.

Так в играх и делают: один список врагов на уровне, и каждый сам знает, как ему атаковать. Новый
вид врага — новый наследник, а цикл менять не нужно.
