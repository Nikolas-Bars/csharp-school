import { existsSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

import en from '@/i18n/locales/en.json'
import ru from '@/i18n/locales/ru.json'
import { SYSTEM_THEMES, THEMES } from '@/theme/themes'

/** Правила из rules/frontend-styles.md, которые можно проверить машиной */

const read = (path: string) => readFileSync(resolve(path), 'utf8')
const tokens = (css: string) =>
  [...css.matchAll(/(--[a-z0-9-]+)\s*:/g)].map(([, name]) => name).sort()

const components = import.meta.glob<string>('../**/*.vue', {
  query: '?raw',
  import: 'default',
  eager: true,
})

describe('темы', () => {
  const reference = tokens(read('src/assets/themes/light.css'))

  it.each(THEMES)('$id: файл с тем же набором токенов, что у светлой, и своей схемой', (theme) => {
    const file = `src/assets/themes/${theme.id}.css`
    expect(existsSync(file)).toBe(true)

    const css = read(file)
    expect(css).toContain(`[data-theme='${theme.id}']`)
    expect(css).toContain(`color-scheme: ${theme.scheme};`)
    expect(tokens(css)).toEqual(reference)
  })

  it('каждая тема подключена в main.css', () => {
    const main = read('src/assets/main.css')
    for (const { id } of THEMES) {
      expect(main).toContain(`@import './themes/${id}.css';`)
    }
  })

  it('у каждой темы и у системной есть название на всех языках', () => {
    for (const messages of [ru, en]) {
      expect(Object.keys(messages.theme)).toEqual(
        expect.arrayContaining(['system', ...THEMES.map(({ id }) => id)]),
      )
    }
  })

  it('скрипт в index.html знает те же темы и то же правило для системной схемы', () => {
    const html = read('index.html')
    const ids = THEMES.map(({ id }) => `'${id}'`).join(', ')

    expect(html).toContain(`const themes = [${ids}]`)
    expect(html).toContain(`dark ? '${SYSTEM_THEMES.dark}' : '${SYSTEM_THEMES.light}'`)
  })

  it('цвета литералами — только в файлах тем, в компонентах var(--color-…)', () => {
    const literal = /#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?|oklch|lab|lch|color)\(/i
    const offenders = Object.entries(components)
      .filter(([, source]) =>
        [...source.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/g)].some(([, css]) =>
          literal.test(css ?? ''),
        ),
      )
      .map(([file]) => file)

    expect(Object.keys(components).length).toBeGreaterThan(0)
    expect(offenders).toEqual([])
  })
})
