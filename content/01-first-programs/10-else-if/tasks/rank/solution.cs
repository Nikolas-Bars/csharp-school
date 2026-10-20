using System;

int score = 850;

if (score >= 1000)
{
    Console.WriteLine("Ранг: Легенда");
}
else if (score >= 500)
{
    Console.WriteLine("Ранг: Герой");
}
else if (score >= 100)
{
    Console.WriteLine("Ранг: Новичок");
}
else
{
    Console.WriteLine("Ранг: Без ранга");
}
