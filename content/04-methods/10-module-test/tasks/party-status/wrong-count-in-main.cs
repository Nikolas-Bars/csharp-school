using System;

int[] first = { 40, 0, 75, 0, 10 };
int[] second = { 0, 0 };
int[] third = { 100, 90 };

Report(first, CountAlive(first));
Report(second, CountAlive(second));
Report(third, CountAlive(third));

static bool IsAlive(int hp)
{
    return hp > 0;
}

static int CountAlive(int[] party)
{
    int count = 0;
    foreach (int hp in party)
    {
        if (IsAlive(hp))
        {
            count++;
        }
    }
    return count;
}

static void Report(int[] party, int alive)
{
    Console.WriteLine($"Живых: {alive} из {party.Length}");
    if (alive == party.Length)
    {
        Console.WriteLine("Все в строю");
    }
    else if (alive == 0)
    {
        Console.WriteLine("Отряд разбит");
    }
    else
    {
        Console.WriteLine("Нужен лекарь");
    }
}
