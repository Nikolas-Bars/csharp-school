using System;

int enemies = 23;
int potions = 0;

for (int enemy = 0; enemy <= enemies; enemy++)
{
    if (enemy % 5 == 0)
    {
        potions++;
    }
}

Console.WriteLine($"Зелий: {potions}");
