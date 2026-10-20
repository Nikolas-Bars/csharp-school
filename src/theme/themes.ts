import type { IconName } from '@/components/ui/BaseIcon.vue'

export type ColorScheme = 'light' | 'dark'

/**
 * Темы оформления (rules/frontend-styles.md). У каждой: файл src/assets/themes/<id>.css,
 * @import в main.css, название theme.<id> во всех языках и id в скрипте index.html.
 * Всё это проверяет src/__tests__/themes.spec.ts.
 */
export const THEMES = [
  { id: 'light', scheme: 'light', icon: 'sun' },
  { id: 'dark', scheme: 'dark', icon: 'moon' },
] as const satisfies readonly { id: string; scheme: ColorScheme; icon: IconName }[]

export type ThemeId = (typeof THEMES)[number]['id']

/** Какую тему включать, когда выбрана системная, — по светлой или тёмной схеме устройства */
export const SYSTEM_THEMES: Record<ColorScheme, ThemeId> = { light: 'light', dark: 'dark' }

export function isThemeId(value: unknown): value is ThemeId {
  return THEMES.some((theme) => theme.id === value)
}
