using System;

int arrows = 23;
int quiverSize = 5;

double fullQuivers = arrows / 5.0;
int leftover = arrows % quiverSize;
Console.WriteLine(fullQuivers);
Console.WriteLine(leftover);
