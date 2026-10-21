using System;

CastSpell("Огненный шар", 20);
CastSpell("Ледяная стрела", 15);

static void CastSpell(string spell, int mana)
{
    Console.WriteLine($"{mana}: -{spell} маны");
}
