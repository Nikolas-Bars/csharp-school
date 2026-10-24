using System;

string enemyName = "Гоблин";
int noise = 3;

Enemy enemy = new Enemy();
enemy.name = enemyName;
Console.WriteLine($"{enemy.name}: {enemy.state} ({(int)enemy.state})");

if (noise > 5)
{
    enemy.state = 2;
}
else
{
    enemy.state = 1;
}
Console.WriteLine($"{enemy.name}: {enemy.state} ({(int)enemy.state})");

enum State { Idle, Walk, Attack }

class Enemy
{
    public string name;
    public State state;
}
