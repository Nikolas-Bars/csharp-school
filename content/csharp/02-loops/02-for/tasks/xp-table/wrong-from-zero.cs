using System;

int xpPerLevel = 150;
int maxLevel = 5;

for (int level = 0; level <= maxLevel; level++)
{
    Console.WriteLine($"Уровень {level}: {level * xpPerLevel} опыта");
}
