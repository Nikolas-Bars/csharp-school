<script setup lang="ts">
import { onBeforeUnmount } from 'vue'

import BaseIcon from '@/components/ui/BaseIcon.vue'
import { useSpeech } from '@/composables/useSpeech'
import { t } from '@/i18n'

/**
 * Кнопка «послушать» английскую фразу. Нет английского голоса на устройстве — кнопки нет
 * (фраза и перевод видны и без звука).
 */
const { text } = defineProps<{ text: string }>()

const { available, speaking, speak, stop } = useSpeech()

// Ушли со страницы или шага — фраза этой кнопки замолкает
onBeforeUnmount(() => {
  if (speaking.value === text) {
    stop()
  }
})
</script>

<template>
  <button
    v-if="available"
    type="button"
    class="speak"
    :class="{ 'speak--playing': speaking === text }"
    :aria-label="t('speech.listen', { text })"
    @click="speak(text)"
  >
    <BaseIcon name="volume" />
  </button>
</template>

<style scoped>
.speak {
  flex: none;
  width: 2.75rem;
  height: 2.75rem;
  border: 1px solid var(--color-primary-border);
  border-radius: var(--radius);
  background: var(--color-surface);
  color: var(--color-accent-text);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.speak:hover {
  background: var(--color-primary-soft);
}

.speak--playing {
  border-color: var(--color-primary);
  background: var(--color-primary);
  color: var(--color-on-accent);
}

.speak:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
</style>
