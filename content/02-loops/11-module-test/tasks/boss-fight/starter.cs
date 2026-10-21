using System;

int seed = 7;
Random random = new Random(seed);
int bossHp = 100;
int round = 0;

// Пока у босса есть здоровье: раунд, урон, крит каждый 3-й раунд, удар, вывод
