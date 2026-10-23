# Список объектов

Свой класс — такой же тип, как `int` или `string`, поэтому из объектов можно собрать список.
Так в играх хранят всех врагов на уровне:

```csharp
using System;
using System.Collections.Generic;

List<Enemy> enemies = new List<Enemy>();
enemies.Add(new Enemy("Гоблин", 30));
enemies.Add(new Enemy("Орк", 60));

foreach (Enemy enemy in enemies)
{
    enemy.TakeDamage(40);
}
foreach (Enemy enemy in enemies)
{
    Console.WriteLine($"{enemy.name}: {enemy.hp} HP");
}

class Enemy
{
    public string name;
    public int hp;

    public Enemy(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }

    public void TakeDamage(int damage)
    {
        hp = Math.Max(hp - damage, 0);
    }
}
```

На экране:

```text
Гоблин: 0 HP
Орк: 20 HP
```

- `List<Enemy>` — список, в котором лежат враги.
- `new Enemy("Гоблин", 30)` прямо в скобках `Add`: враг создаётся и сразу попадает в список,
  отдельная переменная не нужна.
- В `foreach` переменная `enemy` по очереди указывает на каждого врага. В списке лежат ссылки,
  поэтому `enemy.TakeDamage(40)` бьёт настоящих врагов из списка, а не копии.

Правило из модуля «Массивы и списки» остаётся: внутри `foreach` нельзя добавлять и удалять
элементы этого же списка. Менять поля объектов и вызывать их методы — можно.
