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

// Напиши switch по state

enum State { Patrol, Attack, Flee }
