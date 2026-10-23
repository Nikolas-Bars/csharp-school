# Значения по умолчанию

Если полю ничего не присвоить, в нём лежит **значение по умолчанию**: у чисел — `0`, у `bool` —
`false`, у строки — `null` («нулл»). `null` значит «ничего»: в поле пока нет никакой строки.
Подробнее о `null` — в конце модуля.

```csharp
using System;

Hero recruit = new Hero();
Console.WriteLine($"Здоровье: {recruit.hp}");
Console.WriteLine($"Щит: {recruit.hasShield}");
Console.WriteLine($"Имя: [{recruit.name}]");

recruit.name = "Новичок";
recruit.hp = 50;
recruit.hasShield = true;
Console.WriteLine($"{recruit.name}: {recruit.hp} HP, щит: {recruit.hasShield}");

class Hero
{
    public string name;
    public int hp;
    public bool hasShield;
}
```

На экране:

```text
Здоровье: 0
Щит: False
Имя: []
Новичок: 50 HP, щит: True
```

Когда `null` подставляют в текст, получается пустота — поэтому между скобками `[]` ничего нет.

С обычными переменными так не выйдет: прочитать переменную без значения — ошибка CS0165. А поля
нового объекта `new` заполняет сам.
