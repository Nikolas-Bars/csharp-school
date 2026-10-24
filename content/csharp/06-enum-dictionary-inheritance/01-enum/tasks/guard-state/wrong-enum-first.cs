using System;

enum State { Idle, Walk, Attack }

int distance = 8;

State state = State.Idle;
if (distance <= 2)
{
    state = State.Attack;
}
else if (distance <= 10)
{
    state = State.Walk;
}
Console.WriteLine($"Состояние: {state}");
