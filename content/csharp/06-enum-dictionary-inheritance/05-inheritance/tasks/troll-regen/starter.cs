using System;

int heal = 15;

Troll troll = new Troll();
troll.name = "Тролль";

// Выведи тролля, восстанови ему heal здоровья и выведи ещё раз

class Enemy
{
    public string name;
    int hp = 40;

    public void Show()
    {
        Console.WriteLine($"{name}: {hp} HP");
    }
}

class Troll : Enemy
{
    // Добавь метод Regenerate
}
