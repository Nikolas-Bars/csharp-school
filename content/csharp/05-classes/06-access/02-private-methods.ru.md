# private-методы

Прятать можно и методы. Метод-помощник, который нужен только внутри класса, делают `private`:

```csharp
using System;

Shop shop = new Shop(50);
shop.Buy("зелье", 30);
shop.Buy("меч", 40);

class Shop
{
    private int gold;

    public Shop(int gold)
    {
        this.gold = gold;
    }

    public void Buy(string item, int price)
    {
        if (CanPay(price))
        {
            gold -= price;
            Console.WriteLine($"Куплено: {item}");
        }
        else
        {
            Console.WriteLine($"Не хватает золота на {item}");
        }
    }

    private bool CanPay(int price)
    {
        return gold >= price;
    }
}
```

На экране:

```text
Куплено: зелье
Не хватает золота на меч
```

`Buy` вызывает `CanPay`, потому что они в одном классе. А `shop.CanPay(30)` в основном коде —
ошибка CS0122: метод закрыт. Снаружи видно только то, чем объектом и правда нужно
пользоваться, — как кнопки у игрового автомата, а не его провода.
