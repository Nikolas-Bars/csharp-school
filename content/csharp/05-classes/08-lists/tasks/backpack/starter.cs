using System;
using System.Collections.Generic;

int capacity = 10;

List<Item> loot = new List<Item>
{
    new Item("Меч", 5),
    new Item("Щит", 5),
    new Item("Латы", 6)
};

// Создай рюкзак bag и попробуй положить в него каждую вещь из loot

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

// Ниже объяви класс Inventory
