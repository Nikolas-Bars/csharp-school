# Скрипт Unity — это класс

Теперь можно прочитать настоящий скрипт Unity:

```text
using UnityEngine;

public class Player : MonoBehaviour
{
    public int hp;
    public float speed;

    void Start()
    {
        Debug.Log("Игрок появился");
    }

    void Update()
    {
        if (hp <= 0)
        {
            Debug.Log("Игрок повержен");
        }
    }
}
```

- Скрипт — это класс, такой же, как твои `Hero` и `Enemy`. Его поля `hp` и `speed` Unity
  покажет в Inspector. `public` перед `class` значит, что класс открыт для всех, — в Unity
  скрипты пишут так.
- `Start` и `Update` — методы этого класса, без `static`: каждый работает с полями своего
  объекта. `Start` Unity вызывает один раз в начале, `Update` — каждый кадр.
- `: MonoBehaviour` после имени значит, что класс построен на готовом классе Unity
  `MonoBehaviour` («моно-бихейвиор», от английского behaviour — «поведение»). Поэтому Unity
  и умеет сам вызывать `Start` и `Update`. Как это устроено, разберём в следующих модулях.
- `Debug.Log` — вывод в консоль Unity, как `Console.WriteLine`.

`new Player()` здесь не пишут: объект скрипта Unity создаёт сам, когда скрипт добавляют к
объекту в игре. Поэтому и конструктор в скрипте не пишут: начальные значения задают в Inspector
или в `Start`. Всё остальное — поля, методы, `public`, `private`, `null` — работает так же, как
в этом модуле.
