# Сохраняем ответ

Результат сравнения можно положить в переменную типа `bool`:

```csharp
using System;

int hp = 0;
bool isAlive = hp > 0;
Console.WriteLine(isAlive);

string password = "дракон";
Console.WriteLine(password == "дракон");
```

На экране — `False` и `True`.

Строки тоже сравнивают через `==` — буква в букву. `"Дракон"` и `"дракон"` — разные строки:
заглавная Д и строчная д — разные буквы.
