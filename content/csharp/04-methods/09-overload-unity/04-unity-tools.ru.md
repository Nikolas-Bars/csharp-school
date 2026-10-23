# Вывод и Mathf

Два отличия от нашей программы, о которых лучше знать заранее:

- **Вывод.** `Console.WriteLine` в окне Console редактора Unity не появится. Там печатают через
  `Debug.Log("…")` (debug log — «журнал отладки»).
- **Математика.** Вместо `Math` — `Mathf`: `Mathf.Max(a, b)`, `Mathf.Min(a, b)` и
  `Mathf.Clamp(hp, 0, maxHp)` — держит значение в границах, от 0 до `maxHp`. Такой же метод —
  в следующей задаче, своими руками.

```text
void Start()
{
    int hp = 30;
    int damage = 45;
    hp = Mathf.Max(hp - damage, 0);
    Debug.Log($"Осталось {hp} HP");
}
```
