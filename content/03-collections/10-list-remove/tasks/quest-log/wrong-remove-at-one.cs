using System;
using System.Collections.Generic;

List<string> quests = new List<string> { "Найти меч", "Победить волка", "Принести воду" };

quests.Remove("Победить волка");
quests.Add("Спасти деревню");
quests.RemoveAt(1);

for (int i = 0; i < quests.Count; i++)
{
    Console.WriteLine($"{i + 1}. {quests[i]}");
}
Console.WriteLine($"Заданий: {quests.Count}");
