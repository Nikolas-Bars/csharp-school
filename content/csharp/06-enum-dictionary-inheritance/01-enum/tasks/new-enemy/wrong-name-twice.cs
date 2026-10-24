using System;

string enemyName = "Гоблин";
int noise = 3;

Enemy enemy = new Enemy();
enemy.name = enemyName;
Console.WriteLine($"{enemy.name}: {enemy.state} ({enemy.state})");

if (noise > 5)
{
    enemy.state = State.Attack;
}
else
{
    enemy.state = State.Walk;
}
Console.WriteLine($"{enemy.name}: {enemy.state} ({enemy.state})");

enum State { Idle, Walk, Attack }

class Enemy
{
    public string name;
    public State state;
}
