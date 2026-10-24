# protected — для своих и наследников

`private`-поле видно только внутри своего класса — даже наследник его не видит. `public` видно
всем, в том числе основному коду. Между ними есть третий вариант — `protected` («протектед»,
«защищённый»): поле видно в своём классе и в наследниках, но не снаружи.

```csharp
using System;

Troll troll = new Troll();
troll.Regenerate();
troll.Show();

class Enemy
{
    protected int hp = 50;

    public void Show()
    {
        Console.WriteLine($"HP: {hp}");
    }
}

class Troll : Enemy
{
    public void Regenerate()
    {
        hp += 10;
    }
}
```

На экране:

```text
HP: 60
```

`Troll` меняет `hp` в своём методе: поле `protected`, наследнику можно. А `troll.hp = 999;` в
основном коде — ошибка CS0122: снаружи `protected`-поле закрыто, как `private`.

| Слово | Свой класс | Наследник | Основной код |
| --- | --- | --- | --- |
| `private` | да | нет | нет |
| `protected` | да | да | нет |
| `public` | да | да | да |
