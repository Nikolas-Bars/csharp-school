import { existsSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

/** Шрифты отдаёт сам сайт: правила из rules/frontend-styles.md, которые можно проверить машиной */

const read = (path: string) => readFileSync(resolve(path), 'utf8')

const styles = import.meta.glob<string>(['../**/*.css', '../**/*.vue'], {
  query: '?raw',
  import: 'default',
  eager: true,
})

const html = read('index.html')
const fonts = read('src/assets/fonts.css')
const faces = [...fonts.matchAll(/@font-face\s*{([^}]*)}/g)].map(([, body]) => body ?? '')
const fontUrls = [...fonts.matchAll(/url\('([^']+)'\)/g)].map(([, url]) => url ?? '')
const preloads = [...html.matchAll(/<link\s[^>]*rel="preload"[^>]*>/g)].map(([tag]) => tag)

describe('шрифты', () => {
  it('ни index.html, ни стили не ходят за шрифтами на сторонние домены', () => {
    const external = /fonts\.(googleapis|gstatic)\.com|fonts\.bunny\.net|use\.typekit\.net/i
    expect(html).not.toMatch(external)
    expect(html).not.toMatch(/<link\s[^>]*href="(https?:)?\/\//i)

    const offenders = Object.entries(styles)
      .filter(([, source]) => external.test(source) || /url\(\s*['"]?(https?:)?\/\//i.test(source))
      .map(([file]) => file)

    expect(Object.keys(styles)).toContain('../assets/fonts.css')
    expect(offenders).toEqual([])
  })

  it('fonts.css подключён первым в main.css', () => {
    expect(read('src/assets/main.css')).toMatch(/^[^@]*@import '\.\/fonts\.css';/)
  })

  it('каждый @font-face — свой файл woff2 из пакета, с font-display: swap и unicode-range', () => {
    expect(faces.length).toBeGreaterThan(0)
    expect(fontUrls).toHaveLength(faces.length)

    for (const face of faces) {
      expect(face).toContain('font-display: swap;')
      expect(face).toContain('unicode-range:')
    }
    for (const url of fontUrls) {
      expect(url).toMatch(/^@fontsource(-variable)?\/[a-z-]+\/files\/[a-z0-9-]+\.woff2$/)
      expect(existsSync(resolve('node_modules', url))).toBe(true)
    }
  })

  it('у каждого шрифта из токенов есть @font-face', () => {
    const tokens = read('src/assets/tokens.css')
    const families = [...tokens.matchAll(/--font-(?:sans|display|mono):\s*'?([^,']+)'?,/g)].map(
      ([, family]) => family,
    )
    const declared = new Set(faces.map((face) => face.match(/font-family:\s*'?([^;']+)'?;/)?.[1]))

    expect(families).toEqual(['Inter', 'Unbounded', 'JetBrains Mono'])
    for (const family of families) expect(declared).toContain(family)
  })

  it('предзагрузка в index.html — те же файлы, что в fonts.css, с crossorigin', () => {
    expect(preloads.length).toBeGreaterThan(0)

    for (const tag of preloads) {
      const href = tag.match(/href="\/node_modules\/([^"]+)"/)?.[1]
      expect(tag).toContain('as="font"')
      expect(tag).toContain('type="font/woff2"')
      expect(tag).toMatch(/\scrossorigin[\s/>]/)
      expect(fontUrls).toContain(href)
    }
  })
})
