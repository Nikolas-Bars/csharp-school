import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { RunnerClient } from '@/runner/client'
import type { RunResponse, WorkerMessage, WorkerRequest } from '@/runner/protocol'

/** Поддельный Web Worker: запоминает запросы, отвечает тем, что скажет тест */
class FakeWorker extends EventTarget {
  readonly requests: WorkerRequest[] = []
  terminated = false

  postMessage(request: WorkerRequest) {
    this.requests.push(request)
  }

  terminate() {
    this.terminated = true
  }

  reply(message: WorkerMessage) {
    this.dispatchEvent(new MessageEvent('message', { data: message }))
  }

  crash(message: string) {
    this.dispatchEvent(Object.assign(new Event('error'), { message }))
  }
}

const response: RunResponse = {
  compiled: true,
  diagnostics: [],
  output: 'Привет',
  outputLimitExceeded: false,
  error: null,
}

describe('RunnerClient', () => {
  let workers: FakeWorker[]
  let client: RunnerClient

  beforeEach(() => {
    vi.useFakeTimers()
    workers = []
    client = new RunnerClient({
      executionTimeoutMs: 1000,
      createWorker: () => {
        const worker = new FakeWorker()
        workers.push(worker)
        return worker as unknown as Worker
      },
    })
  })

  afterEach(() => vi.useRealTimers())

  /** Дождаться, пока запрос дойдёт до worker (очередь — на промисах) */
  const flush = () => vi.advanceTimersByTimeAsync(0)

  it('передаёт запрос в worker и возвращает его ответ', async () => {
    const outcome = client.run('Console.Write("Привет");', 'Аня')
    await flush()

    const [request] = workers[0]!.requests
    expect(request).toEqual({
      id: 1,
      method: 'run',
      request: { code: 'Console.Write("Привет");', stdin: 'Аня' },
    })

    workers[0]!.reply({ type: 'result', id: 1, response })
    await expect(outcome).resolves.toEqual({ status: 'done', response })
  })

  it('долгая загрузка и компиляция — не таймаут: отсчёт идёт с начала выполнения', async () => {
    const outcome = client.run('while (true) { }')
    await flush()

    await vi.advanceTimersByTimeAsync(10_000)
    workers[0]!.reply({ type: 'executing', id: 1 })
    await vi.advanceTimersByTimeAsync(999)
    workers[0]!.reply({ type: 'result', id: 1, response })

    await expect(outcome).resolves.toMatchObject({ status: 'done' })
  })

  it('между запусками (компиляция копии для set) таймаут не идёт', async () => {
    const outcome = client.test('int height = 3;', [
      { kind: 'output', expected: '3' },
      { kind: 'output', expected: '5', set: { height: 5 } },
    ])
    await flush()

    workers[0]!.reply({ type: 'executing', id: 1 })
    await vi.advanceTimersByTimeAsync(900)
    workers[0]!.reply({ type: 'executed', id: 1 })
    // Медленная компиляция второй копии на слабом телефоне
    await vi.advanceTimersByTimeAsync(10_000)
    workers[0]!.reply({ type: 'executing', id: 1 })
    await vi.advanceTimersByTimeAsync(900)
    workers[0]!.reply({ type: 'executed', id: 1 })
    workers[0]!.reply({
      type: 'result',
      id: 1,
      response: { compiled: true, diagnostics: [], passed: true, results: [] },
    })

    await expect(outcome).resolves.toMatchObject({ status: 'done' })
    expect(workers[0]!.terminated).toBe(false)
  })

  it('бесконечный цикл: по таймауту worker убит, следующий запрос идёт в новый', async () => {
    const endless = client.run('while (true) { }')
    const next = client.run('Console.Write(1);')
    await flush()
    workers[0]!.reply({ type: 'executing', id: 1 })

    await vi.advanceTimersByTimeAsync(1000)
    await expect(endless).resolves.toEqual({ status: 'timeout' })
    expect(workers[0]!.terminated).toBe(true)

    await flush()
    expect(workers).toHaveLength(2)
    expect(workers[1]!.requests[0]).toMatchObject({ id: 2, method: 'run' })
    workers[1]!.reply({ type: 'result', id: 2, response })
    await expect(next).resolves.toMatchObject({ status: 'done' })
  })

  it('запросы идут по одному: следующий — только после ответа на предыдущий', async () => {
    void client.compile('int hp = 1;')
    void client.compile('int hp = 2;')
    await flush()
    expect(workers[0]!.requests).toHaveLength(1)

    workers[0]!.reply({ type: 'result', id: 1, response: { compiled: true, diagnostics: [] } })
    await flush()
    expect(workers[0]!.requests).toHaveLength(2)
  })

  it('runner упал или не загрузился — crashed и новый worker', async () => {
    const failed = client.test('int hp = 1;', [{ kind: 'variable', name: 'hp' }])
    await flush()
    workers[0]!.reply({ type: 'failed', id: 1, message: 'Failed to fetch dotnet.js' })

    await expect(failed).resolves.toEqual({
      status: 'crashed',
      message: 'Failed to fetch dotnet.js',
    })
    expect(workers[0]!.terminated).toBe(true)

    const crashed = client.run('Console.Write(1);')
    await flush()
    workers[1]!.crash('out of memory')
    await expect(crashed).resolves.toEqual({ status: 'crashed', message: 'out of memory' })
  })

  it('ответы на чужой id и сообщение ready не путают очередь', async () => {
    const outcome = client.run('Console.Write(1);')
    await flush()

    workers[0]!.reply({ type: 'ready' })
    workers[0]!.reply({ type: 'result', id: 99, response: { ...response, output: 'чужое' } })
    workers[0]!.reply({ type: 'result', id: 1, response })

    await expect(outcome).resolves.toEqual({ status: 'done', response })
  })
})
