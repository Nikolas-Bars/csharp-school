# Чьи поля видит метод

Метод работает с полями того объекта, у которого его вызвали:

```csharp
using System;

Hero knight = new Hero();
knight.name = "Рыцарь";
knight.hp = 120;

Hero mage = new Hero();
mage.name = "Маг";
mage.hp = 80;

knight.TakeDamage(50);
knight.ShowInfo();
mage.ShowInfo();

class Hero
{
    public string name;
    public int hp;

    public void TakeDamage(int damage)
    {
        hp -= damage;
    }

    public void ShowInfo()
    {
        Console.WriteLine($"{name}: {hp} HP");
    }
}
```

На экране:

```text
Рыцарь: 70 HP
Маг: 80 HP
```

`knight.TakeDamage(50)` уменьшил `hp` рыцаря, а маг не пострадал. Метод один на весь класс, но
каждый раз он работает с полями «своего» героя — того, что стоит перед точкой.

Метод может менять поля, и изменение остаётся в объекте после конца метода. С параметрами так
не было: параметр — копия, она исчезает вместе с методом. А поле живёт, пока живёт объект.
