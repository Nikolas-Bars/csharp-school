# NullReferenceException

Если обратиться через точку к `null`, программа остановится:

```text
Hero hero = new Hero();
Console.WriteLine(hero.weapon.name);   // weapon — null: NullReferenceException
```

Ошибка называется **NullReferenceException** — «исключение пустой ссылки». У пустоты нет ни
полей, ни методов, читать нечего. Компилятор такое не ловит: он не знает, что окажется в поле во
время игры. В Unity это одна из самых частых ошибок — например, когда в поле скрипта забыли
положить нужный объект. Для пустых `public`-полей скрипта Unity называет её по-своему —
UnassignedReferenceException («ссылка не назначена»), но смысл тот же.

Защита — проверить ссылку перед использованием. `!=` — «не равно»:

```csharp
using System;

Hero hero = new Hero();
hero.Attack();
hero.weapon = new Weapon();
hero.weapon.damage = 12;
hero.Attack();

class Hero
{
    public Weapon weapon;

    public void Attack()
    {
        if (weapon != null)
        {
            Console.WriteLine($"Удар оружием: {weapon.damage}");
        }
        else
        {
            Console.WriteLine("Удар кулаком: 1");
        }
    }
}

class Weapon
{
    public int damage;
}
```

На экране:

```text
Удар кулаком: 1
Удар оружием: 12
```

Проверку можно соединить с условием через `&&`: `if (weapon != null && weapon.damage > 0)`. C#
проверяет `&&` и `||` слева направо и останавливается, как только ответ ясен: если `weapon` —
`null`, вторая часть не выполняется, и ошибки не будет.
