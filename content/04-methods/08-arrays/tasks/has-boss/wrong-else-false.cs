using System;

int[] firstRoom = { 30, 100, 45 };
int[] secondRoom = { 20, 60, 99 };

Console.WriteLine(HasBoss(firstRoom));
Console.WriteLine(HasBoss(secondRoom));

static bool HasBoss(int[] enemyHp)
{
    foreach (int hp in enemyHp)
    {
        if (hp >= 100)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    return false;
}
