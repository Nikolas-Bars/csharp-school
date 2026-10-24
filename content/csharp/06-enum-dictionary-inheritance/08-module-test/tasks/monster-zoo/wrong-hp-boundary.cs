using System;
using System.Collections.Generic;

int noise = 7;

List<Monster> zoo = new List<Monster>();
zoo.Add(new Monster("Слизень", 10));
zoo.Add(new Wolf("Волк", 35));
zoo.Add(new Monster("Летучая мышь", 6));

foreach (Monster monster in zoo)
{
    monster.Hear(noise);
}
foreach (Monster monster in zoo)
{
    monster.Act();
}

enum State { Sleep, Hunt, Flee }

class Monster
{
    public string name;
    protected int hp;
    protected State state;

    public Monster(string name, int hp)
    {
        this.name = name;
        this.hp = hp;
    }

    public void Hear(int noise)
    {
        if (hp <= 10)
        {
            state = State.Flee;
        }
        else if (noise >= 5)
        {
            state = State.Hunt;
        }
    }

    public virtual void Act()
    {
        switch (state)
        {
            case State.Sleep:
                Console.WriteLine($"{name} спит");
                break;
            case State.Hunt:
                Console.WriteLine($"{name} охотится");
                break;
            case State.Flee:
                Console.WriteLine($"{name} убегает");
                break;
        }
    }
}

class Wolf : Monster
{
    public Wolf(string name, int hp) : base(name, hp)
    {
    }

    public override void Act()
    {
        if (state == State.Hunt)
        {
            Console.WriteLine($"{name} воет и бросается в атаку");
        }
        else
        {
            base.Act();
        }
    }
}
