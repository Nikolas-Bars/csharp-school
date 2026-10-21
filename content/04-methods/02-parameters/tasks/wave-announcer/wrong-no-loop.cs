using System;

int waves = 4;

AnnounceWave(1);
AnnounceWave(2);
AnnounceWave(3);
AnnounceWave(4);

static void AnnounceWave(int wave)
{
    Console.WriteLine($"Волна {wave}: враги идут!");
}
