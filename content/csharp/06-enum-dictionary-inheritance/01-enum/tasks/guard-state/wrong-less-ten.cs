using System;

int distance = 8;

State state = State.Idle;
if (distance <= 2)
{
    state = State.Attack;
}
else if (distance < 10)
{
    state = State.Walk;
}
Console.WriteLine($"Состояние: {state}");

enum State { Idle, Walk, Attack }
