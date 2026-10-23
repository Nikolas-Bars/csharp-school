using System;

string[] chest = { "монета", "кинжал", "амулет", "яблоко" };
string target = "амулет";

bool found = false;
foreach (string item in chest)
{
    if (item == target)
    {
        found = true;
    }
}

if (found)
{
    Console.WriteLine($"Найден предмет: {target}");
}
else
{
    Console.WriteLine($"Нет предмета: {target}");
}
