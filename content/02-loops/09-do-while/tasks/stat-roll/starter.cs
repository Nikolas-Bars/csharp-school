using System;

int seed = 2024;
Random random = new Random(seed);
int strength;
int tries = 0;

// Бросай два кубика, пока сумма меньше 10: каждая попытка — на экран
