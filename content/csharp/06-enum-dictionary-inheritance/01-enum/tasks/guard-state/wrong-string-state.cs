using System;

int distance = 8;

string state = "Idle";
if (distance <= 2)
{
    state = "Attack";
}
else if (distance <= 10)
{
    state = "Walk";
}
Console.WriteLine($"Состояние: {state}");
