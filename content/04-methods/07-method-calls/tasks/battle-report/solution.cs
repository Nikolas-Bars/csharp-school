using System;

Report("Аня", 40);
Report("Гоблин", 0);
Report("Орк", 15);

static string GetStatus(int hp)
{
    if (hp > 0)
    {
        return "в бою";
    }
    return "вне боя";
}

static void Report(string name, int hp)
{
    Console.WriteLine($"{name}: {hp} HP, {GetStatus(hp)}");
}
