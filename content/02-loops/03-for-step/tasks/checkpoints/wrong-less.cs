using System;

int length = 100;
int step = 25;

for (int meters = step; meters < length; meters += step)
{
    Console.WriteLine($"Чекпоинт: {meters} м");
}
