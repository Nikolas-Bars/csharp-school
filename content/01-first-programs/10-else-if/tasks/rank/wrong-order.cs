using System;

int score = 850;

if (score >= 100)
{
    Console.WriteLine("Ранг: Новичок");
}
else if (score >= 500)
{
    Console.WriteLine("Ранг: Герой");
}
else if (score >= 1000)
{
    Console.WriteLine("Ранг: Легенда");
}
else
{
    Console.WriteLine("Ранг: Без ранга");
}
