/**
 * Протокол runner — зеркало CSharpSchool.Runner.Protocol
 * (csharp-school-runner/src/Runner.Core/Protocol). JSON в camelCase.
 */

export interface CompileRequest {
  code: string
}

export interface RunRequest {
  code: string
  /** Что программа прочитает через Console.ReadLine */
  stdin?: string | null
}

export interface TestRequest {
  code: string
  checks: Check[]
}

/** Проверка решения задачи. Текст для ученика при провале берётся из задачи, не из runner */
export type Check =
  | { kind: 'output'; expected: string; stdin?: string | null; match?: 'exact' | 'contains' }
  | { kind: 'variable'; name: string; type?: string | null }
  | { kind: 'construct'; construct: Construct; min?: number }
  | { kind: 'call'; method: string }

export type Construct =
  'if' | 'else' | 'for' | 'foreach' | 'while' | 'doWhile' | 'switch' | 'method' | 'class'

/** Ошибка или предупреждение компилятора; позиции с 1, message — оригинал на английском */
export interface Diagnostic {
  code: string
  severity: 'error' | 'warning'
  message: string
  arguments: string[]
  line: number
  column: number
  endLine: number
  endColumn: number
}

/** Исключение, на котором упала программа; line — строка кода ученика, если известна */
export interface RuntimeError {
  type: string
  message: string
  line: number | null
}

export interface CompileResponse {
  compiled: boolean
  diagnostics: Diagnostic[]
}

export interface RunResponse extends CompileResponse {
  output: string
  outputLimitExceeded: boolean
  error: RuntimeError | null
}

export interface CheckResult {
  index: number
  passed: boolean
  expected: string | null
  actual: string | null
  outputLimitExceeded: boolean
  error: RuntimeError | null
}

export interface TestResponse extends CompileResponse {
  passed: boolean
  results: CheckResult[]
}

/** Метод runner → запрос и ответ */
export interface RunnerMethods {
  compile: { request: CompileRequest; response: CompileResponse }
  run: { request: RunRequest; response: RunResponse }
  test: { request: TestRequest; response: TestResponse }
}

export type RunnerMethod = keyof RunnerMethods

/** Фронт → Web Worker */
export type WorkerRequest = {
  [M in RunnerMethod]: { id: number; method: M; request: RunnerMethods[M]['request'] }
}[RunnerMethod]

/** Web Worker → фронт */
export type WorkerMessage =
  /** .NET загружен и прогрет */
  | { type: 'ready' }
  /** Код ученика начал выполняться: с этого момента идёт таймаут */
  | { type: 'executing'; id: number }
  | { type: 'result'; id: number; response: unknown }
  /** Runner не загрузился или упал на запросе */
  | { type: 'failed'; id: number; message: string }
