/**
 * Протокол runner — зеркало CSharpSchool.Runner.Protocol
 * (runner/src/Runner.Core/Protocol). JSON в camelCase.
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
  | {
      kind: 'output'
      expected: string
      stdin?: string | null
      match?: 'exact' | 'contains'
      /**
       * Запустить с другими начальными значениями: «имя переменной основного кода → значение».
       * Runner подменяет значение при объявлении (int height = 4; → int height = 5;) только для этой проверки
       */
      set?: Record<string, SetValue> | null
    }
  | { kind: 'variable'; name: string; type?: string | null }
  | { kind: 'construct'; construct: Construct; min?: number }
  /** caller — вызов засчитывается, только если он в теле этого метода (полное имя: Program.Report) */
  | { kind: 'call'; method: string; caller?: string | null }

export type SetValue = number | string | boolean

export type Construct =
  | 'if'
  | 'else'
  | 'for'
  | 'foreach'
  | 'while'
  | 'doWhile'
  | 'switch'
  | 'method'
  | 'class'
  /** break, который выходит из цикла (break в switch не считается) */
  | 'break'
  | 'continue'

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
  /** Проверка с set: почему не получилось подменить значение (программа тогда не запускалась) */
  setError: SetError | null
}

/**
 * notFound — в основном коде (не в цикле, не в блоке и не в методе) нет такой переменной;
 * noValue — объявлена без значения; wrongType — значение не подходит к её типу (type);
 * notCompiled — значения подставились, но код с ними не собирается (variable — все имена через запятую)
 */
export interface SetError {
  variable: string
  problem: 'notFound' | 'noValue' | 'wrongType' | 'notCompiled'
  type: string | null
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
  /** Код ученика закончил выполняться: таймаут сброшен до следующего запуска (проверок вывода бывает несколько) */
  | { type: 'executed'; id: number }
  | { type: 'result'; id: number; response: unknown }
  /** Runner не загрузился или упал на запросе */
  | { type: 'failed'; id: number; message: string }
