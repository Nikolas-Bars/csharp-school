# null — ссылка в никуда

`null` тебе уже знаком: как пустые ячейки `string[]` в модуле «Массивы и списки» и как пустое
поле-строка в этом модуле. С объектами то же самое. Переменная типа
класса хранит ссылку, и эта ссылка может никуда не вести — тогда в ней `null`: объекта нет.

Так бывает у поля, которому ничего не присвоили: у поля-объекта значение по умолчанию — `null`.
Например, у героя есть слот для оружия, но оружия пока нет:

```csharp
using System;

Hero hero = new Hero();
Console.WriteLine(hero.weapon == null);

hero.weapon = new Weapon();
hero.weapon.name = "Лук";
Console.WriteLine(hero.weapon == null);
Console.WriteLine(hero.weapon.name);

class Hero
{
    public Weapon weapon;
}

class Weapon
{
    public string name;
}
```

На экране:

```text
True
False
Лук
```

Поле `weapon` имеет тип `Weapon` — свой класс. Объект может хранить другой объект: у героя —
оружие. `hero.weapon.name` читается по точкам: у героя — оружие, у оружия — имя.

`null` можно присвоить и самому: `hero.weapon = null;` — герой бросил оружие.
