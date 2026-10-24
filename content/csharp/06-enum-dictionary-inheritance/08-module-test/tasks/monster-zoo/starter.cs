using System;
using System.Collections.Generic;

int noise = 7;

List<Monster> zoo = new List<Monster>();
zoo.Add(new Monster("Слизень", 10));
zoo.Add(new Wolf("Волк", 35));
zoo.Add(new Monster("Летучая мышь", 6));

foreach (Monster monster in zoo)
{
    monster.Hear(noise);
}
foreach (Monster monster in zoo)
{
    monster.Act();
}

// Ниже объяви перечисление State

class Monster
{
    public string name;
    protected int hp;
    // Добавь поле state

    public Monster(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }

    public void Hear(int noise)
    {
        // Выбери состояние по здоровью и шуму
    }

    public void Act()
    {
        // Выведи действие монстра по состоянию
    }
}

class Wolf : Monster
{
    public Wolf(string name, int hp) : base(name, hp)
    {
    }

    // Своё действие волка
}
