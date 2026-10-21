using System;
using System.Collections.Generic;

int capacity = 10;

List<Item> loot = new List<Item>
{
    new Item("Меч", 5),
    new Item("Щит", 5),
    new Item("Латы", 6)
};

Inventory bag = new Inventory(capacity);
foreach (Item item in loot)
{
    if (bag.Add(item))
    {
        Console.WriteLine($"Взят: {item.name}");
    }
    else
    {
        Console.WriteLine($"Не влезает: {item.name}");
    }
}
Console.WriteLine($"Вес: {bag.TotalWeight()}/{capacity}");

class Item
{
    public string name;
    public int weight;

    public Item(string name, int weight)
    {
        this.name = name;
        this.weight = weight;
    }
}

class Inventory
{
    private List<Item> items;
    public int maxWeight;

    public Inventory(int maxWeight)
    {
        this.maxWeight = maxWeight;
    }

    public int TotalWeight()
    {
        int total = 0;
        foreach (Item item in items)
        {
            total += item.weight;
        }
        return total;
    }

    public bool Add(Item item)
    {
        if (TotalWeight() + item.weight <= maxWeight)
        {
            items.Add(item);
            return true;
        }
        return false;
    }
}
