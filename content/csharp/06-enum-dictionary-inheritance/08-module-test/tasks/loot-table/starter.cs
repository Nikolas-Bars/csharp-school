using System;
using System.Collections.Generic;

string[] defeated = { "Слизень", "Скелет", "Слизень", "Дракон" };

List<Enemy> enemies = new List<Enemy>();
foreach (string name in defeated)
{
    if (name == "Слизень")
    {
        enemies.Add(new Slime());
    }
    else if (name == "Дракон")
    {
        enemies.Add(new Dragon());
    }
    else
    {
        enemies.Add(new Enemy());
    }
}

// Собери добычу в словарь loot и выведи его

class Enemy
{
    public string Loot()
    {
        return "кость";
    }
}

class Slime : Enemy
{
    // Слизень оставляет слизь
}

class Dragon : Enemy
{
    // Дракон оставляет чешую
}
