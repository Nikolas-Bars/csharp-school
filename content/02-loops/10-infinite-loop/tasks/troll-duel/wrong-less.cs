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
    if (trollHp < 0)
    {
        Console.WriteLine($"Победа в раунде {round}!");
        break;
    }

    heroHp -= trollAttack;
    if (heroHp < 0)
    {
        Console.WriteLine($"Поражение в раунде {round}");
        break;
    }

    Console.WriteLine($"Раунд {round}: герой {heroHp}, тролль {trollHp}");
}
