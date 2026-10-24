<script setup lang="ts">
import { computed } from 'vue'

import BaseSpeakButton from '@/components/ui/BaseSpeakButton.vue'
import { useSpeech } from '@/composables/useSpeech'
import { t } from '@/i18n'
import type { WordCard } from '@/types/api'
import { parseInline } from '@/utils/markdown'

/**
 * Карточка слова по макету: слово, транскрипция, звук, часть речи, перевод, формы неправильного
 * глагола, примеры с «почему так» и «не путай с…». Нет английского голоса — подсказка вместо звука.
 */
const { card } = defineProps<{ card: WordCard }>()

const { available } = useSpeech()
const compare = computed(() => (card.compare === null ? null : parseInline(card.compare)))
</script>

<template>
  <article class="word" :aria-labelledby="`word-${card.id}`">
    <div class="word__head">
      <div class="word__title">
        <div class="word__line">
          <h2 :id="`word-${card.id}`" class="word__word" lang="en">{{ card.word }}</h2>
          <span class="word__ipa">{{ card.ipa }}</span>
        </div>
        <div class="word__tags">
          <span class="word__tag word__tag--pos">{{ t(`words.pos.${card.pos}`) }}</span>
          <span v-if="card.forms" class="word__tag">{{ t('words.card.irregular') }}</span>
        </div>
      </div>
      <BaseSpeakButton :text="card.word" />
    </div>

    <p v-if="!available" class="word__no-voice">{{ t('words.card.noVoice') }}</p>

    <div class="word__section">
      <span class="word__label">{{ t('words.card.translation') }}</span>
      <span class="word__translation">{{ card.translation }}</span>
    </div>

    <div v-if="card.forms" class="word__forms">
      <span class="word__label">{{ t('words.card.forms') }}</span>
      <template v-for="(form, index) in card.forms" :key="index">
        <span v-if="index > 0" aria-hidden="true">→</span>
        <span class="word__form" lang="en">{{ form }}</span>
      </template>
    </div>

    <div class="word__section">
      <span class="word__label">{{ t('words.card.examples') }}</span>
      <div v-for="(example, index) in card.examples" :key="index" class="word__example">
        <BaseSpeakButton :text="example.en" />
        <div class="word__example-text">
          <span class="word__en" lang="en">{{ example.en }}</span>
          <span>{{ example.translation }}</span>
          <span class="word__why">
            <strong class="word__why-label">{{ t('words.card.why') }}</strong> {{ example.why }}
          </span>
        </div>
      </div>
    </div>

    <p v-if="compare" class="word__compare">
      <template v-for="(part, index) in compare" :key="index">
        <strong v-if="part.kind === 'strong'">{{ part.text }}</strong>
        <code v-else-if="part.kind === 'code'">{{ part.text }}</code>
        <mark v-else-if="part.kind === 'mark'" class="word__mark" lang="en">{{ part.text }}</mark>
        <template v-else>{{ part.text }}</template>
      </template>
    </p>
  </article>
</template>

<style scoped>
.word {
  padding: var(--space-6);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.word__head {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
}

.word__title {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  min-width: 0;
}

.word__line {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: var(--space-3) var(--space-4);
}

.word__word {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--font-size-title);
  line-height: 1.1;
  overflow-wrap: anywhere;
}

.word__ipa {
  font-family: var(--font-mono);
  font-size: var(--font-size-lg);
  color: var(--color-text-muted);
}

.word__tags {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.word__tag {
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-sm);
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
  font-weight: 600;
}

.word__tag--pos {
  background: var(--color-primary-soft);
  color: var(--color-accent-text);
}

.word__no-voice {
  margin: 0;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius);
  background: var(--color-surface-muted);
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
  line-height: 1.5;
}

.word__section {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.word__label {
  font-size: var(--font-size-sm);
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--color-text-muted);
}

.word__translation {
  font-size: var(--font-size-xl);
  font-weight: 600;
}

.word__forms {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2) var(--space-3);
}

.word__form {
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-sm);
  background: var(--color-background);
  font-family: var(--font-mono);
}

.word__example {
  padding: var(--space-4);
  border-radius: var(--radius);
  background: var(--color-background);
  display: flex;
  align-items: flex-start;
  gap: var(--space-4);
}

.word__example-text {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.word__en {
  font-size: var(--font-size-lg);
  font-weight: 600;
}

.word__why {
  font-size: var(--font-size-sm);
  line-height: 1.5;
  color: var(--color-text-muted);
}

.word__why-label {
  color: var(--color-accent-text);
}

.word__compare {
  margin: 0;
  padding: var(--space-4);
  border-radius: var(--radius);
  background: var(--color-warning-soft);
  color: var(--color-warning-text);
  line-height: 1.6;
}

.word__mark {
  padding: 0.05em 0.4em;
  border-radius: var(--radius-sm);
  background: var(--color-primary-soft);
  color: var(--color-accent-text);
  font-weight: 600;
}

@media (max-width: 40rem) {
  .word {
    padding: var(--space-5) var(--space-4);
  }

  .word__word {
    font-size: var(--font-size-xxl);
  }
}
</style>
