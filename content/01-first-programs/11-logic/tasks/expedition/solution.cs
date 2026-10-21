using System;

bool hasMap = false;
bool hasGuide = true;
bool isDay = true;

if ((hasMap || hasGuide) && isDay)
{
    Console.WriteLine("Отряд выходит в поход");
}
else
{
    Console.WriteLine("Поход отменён");
}
