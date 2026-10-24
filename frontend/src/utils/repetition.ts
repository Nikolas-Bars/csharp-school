/**
 * Интервальное повторение слов — то же правило, что у сервера (backend: ScheduleReviewTask): новое
 * слово ждёт повторения сразу, «Помню» — следующая коробка, повторить через 3, 7, 14, 30 дней (в
 * четвёртой слово выучено), «Не помню» — обратно в коробку 0, повторить завтра. Нужно гостю: его
 * словарь живёт в браузере.
 */

export const LAST_BOX = 4

/** Через сколько дней повторить слово, попавшее в коробку (индекс — коробка) */
export const BOX_DAYS = [0, 3, 7, 14, 30] as const

/** «Не помню» — завтра */
const FORGOTTEN_DAYS = 1

const DAY = 24 * 60 * 60 * 1000

export interface Schedule {
  box: number
  /** ISO 8601 */
  due_at: string
}

/** Новое слово: коробка 0, повторить сейчас */
export function fresh(now: Date): Schedule {
  return { box: 0, due_at: now.toISOString() }
}

/** Следующее повторение после ответа */
export function schedule(box: number, remembered: boolean, now: Date): Schedule {
  if (!remembered) {
    return { box: 0, due_at: new Date(now.getTime() + FORGOTTEN_DAYS * DAY).toISOString() }
  }
  const next = Math.min(Math.max(box, 0) + 1, LAST_BOX)
  return { box: next, due_at: new Date(now.getTime() + BOX_DAYS[next]! * DAY).toISOString() }
}

/** Конец сегодняшнего дня по часам ученика */
function endOfDay(now: Date): Date {
  const end = new Date(now)
  end.setHours(23, 59, 59, 999)
  return end
}

/** Слово пора повторить: срок — сегодня (по часам ученика) или уже прошёл */
export function isDue(dueAt: string, now: Date): boolean {
  return new Date(dueAt).getTime() <= endOfDay(now).getTime()
}

/** Через сколько дней по календарю ученика повторить: 0 — сегодня (или срок прошёл) */
export function daysUntil(dueAt: string, now: Date): number {
  if (isDue(dueAt, now)) {
    return 0
  }
  const start = (date: Date) => new Date(date.getFullYear(), date.getMonth(), date.getDate())
  return Math.round((start(new Date(dueAt)).getTime() - start(now).getTime()) / DAY)
}
