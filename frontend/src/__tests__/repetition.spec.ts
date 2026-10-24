import { describe, expect, it } from 'vitest'

import { daysUntil, fresh, isDue, schedule } from '@/utils/repetition'

const now = new Date(2026, 9, 24, 10, 0, 0)
const days = (value: number) => new Date(now.getTime() + value * 24 * 60 * 60 * 1000).toISOString()

describe('repetition', () => {
  it('новое слово ждёт повторения сразу', () => {
    expect(fresh(now)).toEqual({ box: 0, due_at: now.toISOString() })
    expect(isDue(fresh(now).due_at, now)).toBe(true)
  })

  it.each([
    [0, 1, 3],
    [1, 2, 7],
    [2, 3, 14],
    [3, 4, 30],
    [4, 4, 30],
  ])('«Помню» из коробки %i — коробка %i, через %i дней (как на сервере)', (box, next, wait) => {
    expect(schedule(box, true, now)).toEqual({ box: next, due_at: days(wait) })
  })

  it('«Не помню» — с начала, завтра', () => {
    expect(schedule(3, false, now)).toEqual({ box: 0, due_at: days(1) })
  })

  it('«сегодня» — до конца дня по часам ученика', () => {
    expect(isDue(new Date(2026, 9, 24, 23, 30).toISOString(), now)).toBe(true)
    expect(isDue(new Date(2026, 9, 25, 0, 5).toISOString(), now)).toBe(false)
    expect(isDue(days(-2), now)).toBe(true)
  })

  it('через сколько дней по календарю', () => {
    expect(daysUntil(days(-1), now)).toBe(0)
    expect(daysUntil(new Date(2026, 9, 24, 22).toISOString(), now)).toBe(0)
    expect(daysUntil(new Date(2026, 9, 25, 1).toISOString(), now)).toBe(1)
    expect(daysUntil(days(30), now)).toBe(30)
  })
})
