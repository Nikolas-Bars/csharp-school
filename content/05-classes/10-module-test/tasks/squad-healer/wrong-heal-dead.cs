using System;
using System.Collections.Generic;

int potion = 40;

List<Unit> squad = new List<Unit>
{
    new Unit("Рыцарь", 30, 100),
    new Unit("Лучник", 60, 70),
    new Unit("Маг", 0, 50)
};

for (int i = 0; i < 2; i++)
{
    Unit weakest = FindWeakest(squad);
    if (weakest != null)
    {
        weakest.Heal(potion);
        Console.WriteLine($"Лечение: {weakest.name}, {weakest.hp}/{weakest.maxHp} HP");
    }
}

int alive = 0;
foreach (Unit unit in squad)
{
    if (unit.IsAlive())
    {
        alive++;
    }
}
Console.WriteLine($"Живых: {alive} из {squad.Count}");

static Unit FindWeakest(List<Unit> units)
{
    Unit weakest = null;
    foreach (Unit unit in units)
    {
        if (weakest == null || unit.hp < weakest.hp)
        {
            weakest = unit;
        }
    }
    return weakest;
}

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

    public bool IsAlive()
    {
        return hp > 0;
    }

    public void Heal(int amount)
    {
        hp = Math.Min(hp + amount, maxHp);
    }
}
