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

if (state == State.Patrol)
{
    Console.WriteLine("Орк ходит дозором");
}
else if (state == State.Attack)
{
    Console.WriteLine("Орк атакует: 12 урона");
}
else
{
    Console.WriteLine("Орк убегает");
}

enum State { Patrol, Attack, Flee }
