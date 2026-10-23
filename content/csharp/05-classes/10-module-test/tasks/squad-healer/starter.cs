using System;
using System.Collections.Generic;

int potion = 40;

List<Unit> squad = new List<Unit>
{
    new Unit("Рыцарь", 30, 100),
    new Unit("Лучник", 60, 70),
    new Unit("Маг", 0, 50)
};

// Два раза вылечи самого слабого живого бойца, потом посчитай живых

// Здесь объяви метод FindWeakest

class Unit
{
    public string name;
    public int hp;
    public int maxHp;

    public Unit(string name, int hp, int maxHp)
    {
        this.name = name;
        this.hp = hp;
        this.maxHp = maxHp;
    }

    // Добавь методы IsAlive и Heal
}
