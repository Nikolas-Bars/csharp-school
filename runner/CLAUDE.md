# C# School Runner

Компиляция и запуск кода ученика в браузере (`../CLAUDE.md` — общие правила, `../rules/` —
подробности). .NET 10, Roslyn (`Microsoft.CodeAnalysis.CSharp`), WebAssembly; .NET на Mac не нужен —
всё в контейнере SDK (`Makefile`).

## Устройство

- `src/Runner.Core` — ядро без привязки к браузеру, одно и то же в браузере и в тестах:
  - `Compilation/StudentCompiler` — компиляция как в Unity: **C# 9, без неявных using**;
    справочник .NET для кода ученика встроен в сборку (`build/StudentReferences.props`:
    System.Runtime, Console, Collections, Linq — больше ученику ничего не доступно);
  - `Compilation/DiagnosticConverter` — ошибки: код, позиции с 1, оригинал и аргументы
    (по ним фронт переводит на русский, `CommonMistakesTests` фиксирует порядок);
  - `Execution/ProgramRunner` — запуск: свой Console (ввод, вывод с лимитом), исключение и строка
    кода ученика (по PDB, сами — см. комментарии);
  - `Checks/CheckRunner` — проверки задачи: вывод, переменная, конструкция (и `break`/`continue`,
    конструктор, свойство), вызов метода (`caller` — только внутри этого метода); формат для
    авторов — `../content/README.md`;
  - `Checks/InitialValues` — `set` у проверки вывода: значение при объявлении переменной основного
    кода заменяется литералом (для массива и `List<T>` — `new int[] { … }`) в синтаксическом дереве
    на тех же строках, копия кода компилируется заново; причина отказа — `SetError` в ответе
    (фронт переводит). Обычный запуск код не меняет. После каждого запуска — `executionFinished`:
    фронт сбрасывает таймаут, компиляция копий под него не попадает;
  - `Protocol/` — JSON протокола (camelCase), зеркало — `frontend/src/runner/protocol.ts`;
  - `RunnerService` — вход: compile, run, test.
- `src/Runner.Browser` — оболочка WebAssembly: `RunnerExports` ([JSExport]) для
  `src/runner/runner.worker.ts` фронта. Таймаут бесконечного цикла — на фронте: worker убивается.
- `tests/Runner.Core.Tests` — xUnit v3 на обычном .NET.
- `tests/Content.Tests` — тесты учебного контента (`../content`): эталоны проходят, неверные
  решения падают как указано, стартовый код не решает задачу, код из теории компилируется,
  формат файлов строгий. Make монтирует весь репозиторий, чтобы `content/` был виден.

## Ловушки однопоточного WebAssembly (проверены в браузере)

- **Нельзя ждать задачу** (`Task.Result`, `Wait`): в Web Worker это вечное зависание. Поэтому у
  файла ученика нет пути — иначе Roslyn считает контрольную сумму для PDB через `Task.Run`
  (`BrowserSafetyTests`).
- **Точка входа — через делегат**, не `MethodInfo.Invoke`: исключение, прошедшее через рефлексию,
  в Mono теряет кадры кода ученика.
- **`Console.In` не читать** — в браузере бросает `PlatformNotSupportedException`.
- **Обрезка (trimming)** включена (6 МБ brotli вместо 11), но `StudentReferences` и Roslyn
  сохраняются целиком: код ученика вызывает методы, о которых при сборке неизвестно.

Тесты на обычном .NET этих ловушек не видят — после изменений в Core проверять в браузере
(`/dev/runner` во фронте).

## Команды

`make help` — список. `make build` (WebAssembly → `../frontend/public/runner`),
`make test`, `make check` (стиль + тесты), `make format`, `make shell`, `make clean`.

## Правила

- Коммиты и пуши — только после подтверждения пользователя; ветка задачи — `NNN`
  (`../rules/task-branches.md`).
- Новые пакеты — по `../rules/dependencies.md`; проверка: `dotnet list package --vulnerable`.
