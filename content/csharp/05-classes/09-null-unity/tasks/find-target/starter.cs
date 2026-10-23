using System;
using System.Collections.Generic;

string targetName = "Орк";

List<Enemy> enemies = new List<Enemy>
{
    new Enemy("Гоблин", 30),
    new Enemy("Орк", 60),
    new Enemy("Тролль", 90)
};

// Найди цель через FindEnemy и выведи её

// Здесь объяви метод FindEnemy

class Enemy
{
    public string name;
    public int hp;

    public Enemy(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }
}
