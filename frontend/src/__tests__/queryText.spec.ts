import { describe, expect, it } from 'vitest'

import { queryText } from '@/utils/queryText'

describe('queryText', () => {
  it('отдаёт значение параметра без пробелов по краям', () => {
    expect(queryText(' anya@example.com ')).toBe('anya@example.com')
  })

  it.each([undefined, null, ['a', 'b'], 42])('не строка (%s) — пустая строка', (value) => {
    expect(queryText(value)).toBe('')
  })
})
