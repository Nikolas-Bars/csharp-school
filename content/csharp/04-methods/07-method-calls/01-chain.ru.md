# Один метод вызывает другой

Из одного метода можно вызвать другой — так из простых способностей собирают сложные:

```csharp
using System;

Console.WriteLine(PowerHit(10));

static int PowerHit(int strength)
{
    int damage = BaseDamage(strength);
    return damage * 2;
}

static int BaseDamage(int strength)
{
    return strength + 5;
}
```

Напечатается `30`. Порядок такой:

1. Основной код вызывает `PowerHit(10)`.
2. `PowerHit` вызывает `BaseDamage(10)` и ждёт ответа.
3. `BaseDamage` возвращает 15 обратно в `PowerHit`.
4. `PowerHit` возвращает 15 * 2 = 30 в основной код, и тот печатает ответ.

Каждый `return` отдаёт ответ тому, кто вызвал метод, — на шаг назад.
