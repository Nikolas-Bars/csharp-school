using System;

int wolves = 3;

Quest quest = new Quest("Волки", 5);
quest.AddProgress(wolves);
Console.WriteLine($"{quest.Title}: {quest.Progress}/{quest.Goal}");
quest.AddProgress(4);
Console.WriteLine($"{quest.Title}: {quest.Progress}/{quest.Goal}");
if (quest.IsDone())
{
    Console.WriteLine("Квест выполнен!");
}

class Quest
{
    public string Title { get; set; }
    public int Progress { get; set; }
    public int Goal { get; set; }

    public Quest(string title, int goal)
    {
        Title = title;
        Goal = goal;
    }

    public void AddProgress(int amount)
    {
        Progress = Math.Min(Progress + amount, Goal);
    }

    public bool IsDone()
    {
        return Progress == Goal;
    }
}
