# У каждого объекта свои поля

Каждый `new` создаёт отдельный объект со своим набором полей:

```csharp
using System;

Hero knight = new Hero();
knight.name = "Рыцарь";
knight.hp = 120;

Hero mage = new Hero();
mage.name = "Маг";
mage.hp = 70;

mage.hp -= 30;
Console.WriteLine($"{knight.name}: {knight.hp} HP");
Console.WriteLine($"{mage.name}: {mage.hp} HP");

class Hero
{
    public string name;
    public int hp;
}
```

На экране:

```text
Рыцарь: 120 HP
Маг: 40 HP
```

Удар по магу уменьшил только `mage.hp` — у рыцаря здоровье прежнее. Класс один, а героев два,
и у каждого свои значения. Как два меча, выкованных по одному чертежу: один можно сломать, а
второй останется целым.
