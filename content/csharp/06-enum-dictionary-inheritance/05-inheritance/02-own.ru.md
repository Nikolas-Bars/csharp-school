# Наследник добавляет своё

Наследник может добавить своё — поля и методы, которых у базового класса нет:

```csharp
using System;

Orc orc = new Orc();
orc.name = "Грох";
orc.rage = 3;
orc.Roar();

Goblin goblin = new Goblin();
goblin.name = "Скрип";
goblin.Steal();

class Enemy
{
    public string name;
}

class Orc : Enemy
{
    public int rage;

    public void Roar()
    {
        Console.WriteLine($"{name} рычит! Ярость: {rage}");
    }
}

class Goblin : Enemy
{
    public void Steal()
    {
        Console.WriteLine($"{name} крадёт монету");
    }
}
```

На экране:

```text
Грох рычит! Ярость: 3
Скрип крадёт монету
```

В методе `Roar` можно писать `name`: это поле орк получил от `Enemy`. А вот `goblin.Roar()` —
ошибка CS1061: рычать умеет только орк, у гоблина и у `Enemy` такого метода нет.
