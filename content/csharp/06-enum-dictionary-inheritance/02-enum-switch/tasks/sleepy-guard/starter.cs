using System;

int noise = 7;

Guard guard = new Guard();
guard.Act();
guard.Hear(noise);
guard.Act();

// Ниже объяви перечисление State

class Guard
{
    // Добавь поле state

    public void Hear(int noise)
    {
        // Поменяй состояние по громкости шума
    }

    public void Act()
    {
        // Выведи действие стражника через switch по state
    }
}
