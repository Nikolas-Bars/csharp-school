using System;

string spell = "Огненный шар";

Console.WriteLine($"{spell.ToUpper()}!");
if (spell.ToLower().Contains("огн"))
{
    Console.WriteLine("Стихия: огонь");
}
else
{
    Console.WriteLine("Стихия: неизвестна");
}
