# Осторожно с continue в while

В for шаг выполняется сам, даже после `continue`. В while шаг — обычная команда в теле, и
`continue` может её перепрыгнуть:

```text
int i = 1;
while (i <= 5)
{
    if (i == 3)
    {
        continue;   // i++ ниже не выполнится — i навсегда останется 3
    }
    Console.WriteLine(i);
    i++;
}
```

Это бесконечный цикл. Поэтому в while со `continue` счётчик меняют **до** `continue` — или
берут цикл for.
