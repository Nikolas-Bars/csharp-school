# public и private

Слова `public` и `private` называют **модификаторами доступа**: они говорят, кто может
пользоваться членом класса — полем или методом.

- `public` («открытый») — кто угодно: и код класса, и основной код.
- `private` («прайвит», по-английски «личный») — только код внутри этого класса: его методы,
  конструктор, свойства.

Если ничего не написать, член класса будет `private`. Поэтому без `public` основной код поле не
видит — ошибка CS0122.

Зачем вообще прятать? Пока `hp` открыто, любой код может написать `hero.hp = -500;` — и у героя
невозможное здоровье. Надёжнее спрятать поле, а наружу открыть только безопасные действия:

```csharp
using System;

Hero hero = new Hero("Артур", 100);
hero.TakeDamage(30);
Console.WriteLine(hero.GetHp());
hero.TakeDamage(500);
Console.WriteLine(hero.GetHp());

class Hero
{
    public string name;
    private int hp;

    public Hero(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }

    public void TakeDamage(int damage)
    {
        hp = Math.Max(hp - damage, 0);
    }

    public int GetHp()
    {
        return hp;
    }
}
```

На экране:

```text
70
0
```

Поле `hp` — `private`: снаружи его не испортить. Меняется оно только через `TakeDamage`, а тот
не пускает здоровье ниже нуля. Узнать здоровье можно через `GetHp`.

У самого класса модификатор можно не писать — в наших программах это ни на что не влияет. В
Unity пишут `public class`.
