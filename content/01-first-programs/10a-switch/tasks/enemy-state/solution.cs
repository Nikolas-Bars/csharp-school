using System;

int state = 2;

switch (state)
{
    case 0:
        Console.WriteLine("Враг спит");
        break;
    case 1:
        Console.WriteLine("Враг патрулирует");
        break;
    case 2:
        Console.WriteLine("Враг атакует");
        break;
    default:
        Console.WriteLine("Враг растерян");
        break;
}
