using System;

int hp = 40;
int distance = 3;

State state = State.Patrol;
if (distance <= 5)
{
    state = State.Attack;
}
if (hp < 15)
{
    state = State.Flee;
}

switch (state)
{
    case State.Patrol:
        Console.WriteLine("Орк ходит дозором");
    case State.Attack:
        Console.WriteLine("Орк атакует: 12 урона");
        break;
    case State.Flee:
        Console.WriteLine("Орк убегает");
        break;
}

enum State { Patrol, Attack, Flee }
