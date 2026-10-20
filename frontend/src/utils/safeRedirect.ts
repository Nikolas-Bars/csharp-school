/**
 * Куда вернуться после входа: только путь внутри сайта. Полные адреса, «//evil.example» и
 * «/\evil.example» (браузер читает его как «//») отбрасываются — иначе ссылка «войти и
 * вернуться» могла бы увести на чужой сайт.
 */
export function safeRedirect(target: unknown, fallback = '/'): string {
  return typeof target === 'string' && /^\/(?![/\\])/.test(target) ? target : fallback
}
