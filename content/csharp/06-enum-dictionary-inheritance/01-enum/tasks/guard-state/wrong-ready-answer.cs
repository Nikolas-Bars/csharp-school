using System;

int distance = 8;

State state = State.Walk;
Console.WriteLine("Состояние: Walk");

enum State { Idle, Walk, Attack }
