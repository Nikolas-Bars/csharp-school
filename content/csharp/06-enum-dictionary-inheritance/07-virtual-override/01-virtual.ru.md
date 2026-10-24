# Своя атака у каждого: virtual и override

Все враги атакуют, но по-разному: орк бьёт дубиной, гоблин кидает камень. Метод `Attack` нужен
каждому врагу — но у каждого вида свой.

Для этого метод базового класса помечают `virtual` («виртуальный»: наследник может его заменить),
а наследник пишет свою версию со словом `override` («оверрайд», «переопределить»):

```csharp
using System;

Enemy rat = new Enemy("Крыса");
Orc orc = new Orc("Грох");
rat.Attack();
orc.Attack();

class Enemy
{
    public string name;

    public Enemy(string name)
    {
        this.name = name;
    }

    public virtual void Attack()
    {
        Console.WriteLine($"{name} кусает: 2 урона");
    }
}

class Orc : Enemy
{
    public Orc(string name) : base(name)
    {
    }

    public override void Attack()
    {
        Console.WriteLine($"{name} бьёт дубиной: 12 урона");
    }
}
```

На экране:

```text
Крыса кусает: 2 урона
Грох бьёт дубиной: 12 урона
```

- `public virtual void Attack()` — у любого врага есть атака, и наследник может её заменить.
- `public override void Attack()` — у орка своя атака. Имя, параметры, тип ответа и `public` — те
  же, что у метода базового класса.

Наследник без своего `override` атакует по-старому — версией базового класса.

> В Unity `Start` и `Update` — не `virtual`: их пишут без `override`. `override void Update()` —
> ошибка CS0115.
