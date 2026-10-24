using System;

int noise = 7;

Guard guard = new Guard();
guard.Act();
guard.Hear(noise);
guard.Act();

class Guard
{
    public string state = "Idle";

    public void Hear(int noise)
    {
        if (noise >= 10)
        {
            state = "Alarm";
        }
        else if (noise >= 5)
        {
            state = "Alert";
        }
    }

    public void Act()
    {
        switch (state)
        {
            case "Idle":
                Console.WriteLine("Стражник дремлет");
                break;
            case "Alert":
                Console.WriteLine("Стражник осматривается");
                break;
            case "Alarm":
                Console.WriteLine("Стражник поднимает тревогу!");
                break;
        }
    }
}
