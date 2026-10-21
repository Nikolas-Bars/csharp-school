using System;

int state = 2;

if (state == 0)
{
    Console.WriteLine("Враг спит");
}
else if (state == 1)
{
    Console.WriteLine("Враг патрулирует");
}
else if (state == 2)
{
    Console.WriteLine("Враг атакует");
}
else
{
    Console.WriteLine("Враг растерян");
}
