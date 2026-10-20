// Хеши встроенных <script> собранного index.html для CSP (script-src 'sha256-…'): так браузер
// выполнит только эти скрипты, без 'unsafe-inline'. Печатает их через пробел.
// Запуск при сборке образа: node csp-hashes.mjs dist/index.html
import { createHash } from 'node:crypto'
import { readFileSync } from 'node:fs'

const html = readFileSync(process.argv[2], 'utf8')
const inline = [...html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)].filter(
  ([, attributes]) => !/\bsrc\s*=/i.test(attributes),
)

const hashes = inline.map(
  ([, , body]) => `'sha256-${createHash('sha256').update(body, 'utf8').digest('base64')}'`,
)

console.log(hashes.join(' '))
