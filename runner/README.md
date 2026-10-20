# C# School Runner

Компилятор и запуск C# прямо в браузере ученика: Roslyn + .NET в WebAssembly внутри Web Worker.
Сервер код учеников не выполняет никогда.

```bash
make build   # собрать и положить во фронт: ../frontend/public/runner
make check   # стиль + тесты ядра
make help    # остальные команды
```

Проверить в браузере: `make start` во фронте → http://localhost:5390/dev/runner (только при разработке).
