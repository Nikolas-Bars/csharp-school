using System;

string enemyName = "Гоблин";
int noise = 3;

Enemy enemy = new Enemy();
enemy.name = enemyName;
Console.WriteLine($"{enemy.name}: {enemy.state} ({(int)enemy.state})");

if (noise > 5)
{
    enemy.state = State.Attack;
}
else
{
    enemy.state = State.Walk;
}
Console.WriteLine($"{enemy.name}: {enemy.state} ({(int)enemy.state})");

enum State { Walk, Idle, Attack }

class Enemy
{
    public string name;
    public State state;
}
