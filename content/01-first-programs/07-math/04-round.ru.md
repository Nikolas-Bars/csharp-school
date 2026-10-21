# Округление: Math.Round

Помнишь `(int)` из урока про дробные числа? Он отбрасывает дробную часть: 7.8 становится 7.
А чтобы округлить до ближайшего целого, в Math есть метод `Math.Round`:

```csharp
using System;

Console.WriteLine(Math.Round(7.8));
Console.WriteLine(Math.Round(7.2));
int hp = (int)Math.Round(7.8);
Console.WriteLine(hp);
```

На экране — `8`, `7` и `8`. Math.Round возвращает `double`, поэтому, чтобы положить ответ в
`int`, нужно ещё `(int)`.

Осторожно с числами ровно посередине, на .5: `Math.Round(2.5)` даёт 2, а `Math.Round(3.5)` —
4. Такое число C# округляет к **чётному** соседу, а не всегда вверх, как в школе.
