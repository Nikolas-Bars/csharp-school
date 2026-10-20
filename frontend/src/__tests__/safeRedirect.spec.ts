import { describe, expect, it } from 'vitest'

import { safeRedirect } from '@/utils/safeRedirect'

describe('safeRedirect', () => {
  it('пропускает путь внутри сайта', () => {
    expect(safeRedirect('/lessons/1?step=2')).toBe('/lessons/1?step=2')
  })

  it.each([
    'https://evil.example',
    '//evil.example',
    '/\\evil.example',
    'javascript:alert(1)',
    '',
    undefined,
    ['/lessons'],
  ])('не уводит на чужой сайт: %s', (target) => {
    expect(safeRedirect(target)).toBe('/')
  })
})
