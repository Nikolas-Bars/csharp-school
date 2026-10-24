using System;

string enemyName = "Гоблин";
int noise = 3;

Enemy enemy = new Enemy();
enemy.name = enemyName;
enemy.state = State.Walk;
Console.WriteLine("Гоблин: Idle (0)");
Console.WriteLine("Гоблин: Walk (1)");

enum State { Idle, Walk, Attack }

class Enemy
{
    public string name;
    public State state;
}
