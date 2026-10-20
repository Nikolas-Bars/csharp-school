import type {
  Check,
  CompileResponse,
  RunnerMethod,
  RunnerMethods,
  RunResponse,
  TestResponse,
  WorkerMessage,
  WorkerRequest,
} from './protocol'

/**
 * Чем кончился запрос к runner:
 * - done — ответ runner (в том числе с ошибками компиляции или исключением программы);
 * - timeout — код ученика выполнялся дольше лимита (скорее всего бесконечный цикл), worker убит;
 * - crashed — runner не загрузился или упал, worker перезапущен.
 */
export type RunnerOutcome<T> =
  { status: 'done'; response: T } | { status: 'timeout' } | { status: 'crashed'; message: string }

export interface RunnerOptions {
  createWorker?: () => Worker
  /** Сколько может выполняться код ученика; компиляция в лимит не входит */
  executionTimeoutMs?: number
}

export const DEFAULT_EXECUTION_TIMEOUT_MS = 5000

const createRunnerWorker = () =>
  new Worker(new URL('./runner.worker.ts', import.meta.url), { type: 'module' })

/**
 * Мост к runner в Web Worker. Запросы идут строго по очереди. Таймаут считается с момента,
 * когда runner сообщил о начале выполнения кода ученика, — долгая первая загрузка .NET
 * и компиляция за бесконечный цикл не принимаются.
 */
export class RunnerClient {
  private worker: Worker | null = null
  private queue: Promise<unknown> = Promise.resolve()
  private nextId = 1
  private readonly createWorker: () => Worker
  private readonly executionTimeoutMs: number

  constructor(options: RunnerOptions = {}) {
    this.createWorker = options.createWorker ?? createRunnerWorker
    this.executionTimeoutMs = options.executionTimeoutMs ?? DEFAULT_EXECUTION_TIMEOUT_MS
  }

  compile(code: string): Promise<RunnerOutcome<CompileResponse>> {
    return this.enqueue('compile', { code })
  }

  run(code: string, stdin?: string): Promise<RunnerOutcome<RunResponse>> {
    return this.enqueue('run', { code, stdin: stdin ?? null })
  }

  test(code: string, checks: Check[]): Promise<RunnerOutcome<TestResponse>> {
    return this.enqueue('test', { code, checks })
  }

  /** Начать загрузку и прогрев .NET заранее, пока ученик читает теорию */
  preload(): void {
    this.ensureWorker()
  }

  dispose(): void {
    this.worker?.terminate()
    this.worker = null
  }

  private enqueue<M extends RunnerMethod>(
    method: M,
    request: RunnerMethods[M]['request'],
  ): Promise<RunnerOutcome<RunnerMethods[M]['response']>> {
    const task = this.queue.then(() => this.send(method, request))
    this.queue = task
    return task
  }

  private send<M extends RunnerMethod>(
    method: M,
    request: RunnerMethods[M]['request'],
  ): Promise<RunnerOutcome<RunnerMethods[M]['response']>> {
    const worker = this.ensureWorker()
    const id = this.nextId++

    return new Promise((resolve) => {
      let timer: ReturnType<typeof setTimeout> | undefined

      const finish = (outcome: RunnerOutcome<RunnerMethods[M]['response']>, restart = false) => {
        clearTimeout(timer)
        worker.removeEventListener('message', onMessage)
        worker.removeEventListener('error', onError)
        if (restart) {
          this.dispose()
        }
        resolve(outcome)
      }

      const onMessage = ({ data }: MessageEvent<WorkerMessage>) => {
        if (data.type === 'ready' || data.id !== id) {
          return
        }
        if (data.type === 'executing') {
          // Каждый запуск программы (у проверки вывода их несколько) — свой отсчёт
          clearTimeout(timer)
          timer = setTimeout(() => finish({ status: 'timeout' }, true), this.executionTimeoutMs)
        } else if (data.type === 'result') {
          finish({ status: 'done', response: data.response as RunnerMethods[M]['response'] })
        } else {
          finish({ status: 'crashed', message: data.message }, true)
        }
      }

      const onError = (event: ErrorEvent) =>
        finish({ status: 'crashed', message: event.message }, true)

      worker.addEventListener('message', onMessage)
      worker.addEventListener('error', onError)
      worker.postMessage({ id, method, request } as WorkerRequest)
    })
  }

  private ensureWorker(): Worker {
    if (this.worker !== null) {
      return this.worker
    }

    this.worker = this.createWorker()
    return this.worker
  }
}
