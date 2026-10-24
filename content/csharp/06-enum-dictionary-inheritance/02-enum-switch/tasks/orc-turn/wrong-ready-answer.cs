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

Console.WriteLine("Орк атакует: 12 урона");

enum State { Patrol, Attack, Flee }
