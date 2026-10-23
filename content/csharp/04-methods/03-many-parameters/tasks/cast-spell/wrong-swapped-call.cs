using System;

CastSpell(20, "Огненный шар");
CastSpell(15, "Ледяная стрела");

static void CastSpell(string spell, int mana)
{
    Console.WriteLine($"{spell}: -{mana} маны");
}
