using System;

ShowHero("Аня", 100, 40);
ShowHero("Борис", 80, 65);

static void ShowHero(string name, int hp, int mana)
{
    Console.WriteLine($"{name}: здоровье {hp}, мана {mana}");
}
