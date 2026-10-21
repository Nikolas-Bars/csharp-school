using System;

int heroHp = 40;
int heroAttack = 9;
int trollHp = 36;
int trollAttack = 7;
int round = 0;

while (true)
{
    round++;
    trollHp -= heroAttack;
    heroHp -= trollAttack;
    Console.WriteLine($"Раунд {round}: герой {heroHp}, тролль {trollHp}");

    if (trollHp <= 0)
    {
        Console.WriteLine($"Победа в раунде {round}!");
        break;
    }
    if (heroHp <= 0)
    {
        Console.WriteLine($"Поражение в раунде {round}!");
        break;
    }
}
