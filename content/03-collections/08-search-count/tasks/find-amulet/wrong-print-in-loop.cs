using System;

string[] chest = { "монета", "кинжал", "амулет", "яблоко" };
string target = "амулет";

foreach (string item in chest)
{
    if (item == target)
    {
        Console.WriteLine($"Найден предмет: {target}");
    }
    else
    {
        Console.WriteLine($"Нет предмета: {target}");
    }
}
