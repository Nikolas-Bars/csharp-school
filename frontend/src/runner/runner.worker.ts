/**
 * Web Worker с runner: .NET в WebAssembly (csharp-school-runner, сборка — в public/runner).
 * Отдельный поток: бесконечный цикл ученика не вешает страницу, а client.ts по таймауту
 * просто убивает этот worker и запускает новый.
 */
import type { RunnerMethod, WorkerMessage, WorkerRequest } from './protocol'

/** Методы RunnerExports (csharp-school-runner/src/Runner.Browser): JSON протокола в обе стороны */
type RunnerExports = Record<'Compile' | 'Run' | 'Test', (request: string) => string> & {
  Warmup(): void
}

interface DotnetRuntime {
  setModuleImports(module: string, imports: Record<string, () => void>): void
  getConfig(): { mainAssemblyName?: string }
  getAssemblyExports(
    assembly: string,
  ): Promise<{ CSharpSchool: { Runner: { Browser: { RunnerExports: RunnerExports } } } }>
}

const scope = globalThis as unknown as {
  postMessage(message: WorkerMessage): void
  addEventListener(type: 'message', listener: (event: MessageEvent<WorkerRequest>) => void): void
}

/**
 * Полный адрес с доменом, а не путь: к пути «/runner/…» Vite при разработке дописывает ?import
 * и отказывается отдавать файл из public. Загрузчик .NET — обычный файл, обрабатывать его не нужно
 */
const DOTNET_URL = new URL(
  `${import.meta.env.BASE_URL}runner/_framework/dotnet.js`,
  location.origin,
).href

const EXPORTS: Record<RunnerMethod, keyof RunnerExports> = {
  compile: 'Compile',
  run: 'Run',
  test: 'Test',
}

/** Запросы идут по одному (client.ts ждёт ответа), поэтому id текущего — одна переменная */
let currentId = 0

async function load(): Promise<RunnerExports> {
  const { dotnet } = (await import(/* @vite-ignore */ DOTNET_URL)) as {
    dotnet: { create(): Promise<DotnetRuntime> }
  }
  const runtime = await dotnet.create()

  runtime.setModuleImports('runner', {
    executionStarted: () => scope.postMessage({ type: 'executing', id: currentId }),
  })

  const assembly = runtime.getConfig().mainAssemblyName ?? 'CSharpSchool.Runner.Browser'
  const exports = (await runtime.getAssemblyExports(assembly)).CSharpSchool.Runner.Browser
    .RunnerExports

  exports.Warmup()
  scope.postMessage({ type: 'ready' })

  return exports
}

const ready = load()
// Ошибка загрузки придёт ответом на запрос; здесь — только чтобы не было «unhandled rejection»
ready.catch(() => undefined)

// Только addEventListener: если назначить self.onmessage до загрузки, dotnet.create() в worker
// не завершается никогда — runtime .NET сам пользуется этим обработчиком
scope.addEventListener('message', async ({ data }) => {
  currentId = data.id

  try {
    const exports = await ready
    const response = exports[EXPORTS[data.method]](JSON.stringify(data.request)) as string
    scope.postMessage({ type: 'result', id: data.id, response: JSON.parse(response) as unknown })
  } catch (error) {
    scope.postMessage({
      type: 'failed',
      id: data.id,
      message: error instanceof Error ? error.message : String(error),
    })
  }
})
