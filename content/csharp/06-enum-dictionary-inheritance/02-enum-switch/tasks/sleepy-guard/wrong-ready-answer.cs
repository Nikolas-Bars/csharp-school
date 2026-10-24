using System;

int noise = 7;

Guard guard = new Guard();
guard.Hear(noise);
Console.WriteLine("Стражник дремлет");
Console.WriteLine("Стражник осматривается");

enum State { Idle, Alert, Alarm }

class Guard
{
    public State state;

    public void Hear(int noise)
    {
        if (noise >= 10)
        {
            state = State.Alarm;
        }
        else if (noise >= 5)
        {
            state = State.Alert;
        }
    }

    public void Act()
    {
        switch (state)
        {
            case State.Idle:
                Console.WriteLine("Стражник дремлет");
                break;
            case State.Alert:
                Console.WriteLine("Стражник осматривается");
                break;
            case State.Alarm:
                Console.WriteLine("Стражник поднимает тревогу!");
                break;
        }
    }
}
