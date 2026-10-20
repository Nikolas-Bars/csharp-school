using System;

int arrows = 23;
int quiverSize = 5;

int fullQuivers = arrows % quiverSize;
int leftover = arrows / quiverSize;
Console.WriteLine(fullQuivers);
Console.WriteLine(leftover);
