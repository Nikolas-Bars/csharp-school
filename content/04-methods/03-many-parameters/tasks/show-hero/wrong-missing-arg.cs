using System;

ShowHero("Аня", 100);
ShowHero("Борис", 80);

static void ShowHero(string name, int hp, int mana)
{
    Console.WriteLine($"{name}: здоровье {hp}, мана {mana}");
}
