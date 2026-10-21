# Конструктор

Заполнять поля по одному долго, и легко забыть какое-нибудь — тогда в нём останется `0` или
`null`. Поэтому у класса есть **конструктор** — особый метод, который выполняется в момент `new`
и сразу заполняет новый объект:

```csharp
using System;

Hero knight = new Hero("Рыцарь", 120);
Hero mage = new Hero("Маг", 70);
Console.WriteLine($"{knight.name}: {knight.hp} HP");
Console.WriteLine($"{mage.name}: {mage.hp} HP");

class Hero
{
    public string name;
    public int hp;

    public Hero(string heroName, int startHp)
    {
        name = heroName;
        hp = startHp;
    }
}
```

На экране:

```text
Рыцарь: 120 HP
Маг: 70 HP
```

- Имя конструктора — то же, что у класса: `Hero`.
- Типа ответа у конструктора нет, даже `void`: он ничего не возвращает, а заполняет объект.
- Аргументы передают в скобках после `new Hero` — как при вызове метода: по порядку, через
  запятую.
- `public` нужен, чтобы основной код мог создавать героев.

Теперь целый герой создаётся одной строкой.
