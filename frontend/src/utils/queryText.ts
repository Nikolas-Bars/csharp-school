/**
 * Параметр адреса строкой: `?email=anya@example.com` → `anya@example.com`. Параметра нет или он
 * повторён (`?email=a&email=b` — vue-router отдаёт массив) — пустая строка.
 */
export function queryText(value: unknown): string {
  return typeof value === 'string' ? value.trim() : ''
}
