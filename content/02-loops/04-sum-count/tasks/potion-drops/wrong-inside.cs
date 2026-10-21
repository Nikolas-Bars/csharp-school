using System;

int enemies = 23;

for (int enemy = 1; enemy <= enemies; enemy++)
{
    int potions = 0;
    if (enemy % 5 == 0)
    {
        Console.WriteLine($"Враг {enemy} уронил зелье");
        potions++;
    }
}

Console.WriteLine($"Зелий: {potions}");
