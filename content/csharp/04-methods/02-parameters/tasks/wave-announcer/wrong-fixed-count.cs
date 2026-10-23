using System;

int waves = 4;

for (int i = 1; i <= 4; i++)
{
    AnnounceWave(i);
}

static void AnnounceWave(int wave)
{
    Console.WriteLine($"Волна {wave}: враги идут!");
}
