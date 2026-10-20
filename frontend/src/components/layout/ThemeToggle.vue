<script setup lang="ts">
import { computed } from 'vue'

import BaseIcon, { type IconName } from '@/components/ui/BaseIcon.vue'
import { t } from '@/i18n'
import { useThemeStore, type ThemeChoice } from '@/stores/theme'
import { THEMES } from '@/theme/themes'

const theme = useThemeStore()

const icon = computed<IconName>(
  () => THEMES.find(({ id }) => id === theme.choice)?.icon ?? 'monitor',
)

const name = (choice: ThemeChoice) => t(`theme.${choice}`)
</script>

<template>
  <!-- Иконка — что включено сейчас; подпись говорит и что сейчас, и что будет по нажатию -->
  <button
    class="theme-toggle"
    type="button"
    :aria-label="t('theme.toggle', { current: name(theme.choice), next: name(theme.next) })"
    :title="name(theme.choice)"
    @click="theme.choice = theme.next"
  >
    <BaseIcon :name="icon" />
  </button>
</template>

<style scoped>
.theme-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.75rem;
  height: 2.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  background: var(--color-surface);
  color: var(--color-text);
  cursor: pointer;
}

.theme-toggle:hover {
  background: var(--color-surface-muted);
}

.theme-toggle:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
</style>
