using System;

string spell = "Огненный шар";

spell.ToUpper();
Console.WriteLine($"{spell}!");
if (spell.ToLower().Contains("огн"))
{
    Console.WriteLine("Стихия: огонь");
}
else
{
    Console.WriteLine("Стихия: неизвестна");
}
