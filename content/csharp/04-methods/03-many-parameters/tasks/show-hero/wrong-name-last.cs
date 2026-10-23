using System;

ShowHero(100, 40, "Аня");
ShowHero(80, 65, "Борис");

static void ShowHero(string name, int hp, int mana)
{
    Console.WriteLine($"{name}: здоровье {hp}, мана {mana}");
}
