<script setup lang="ts">
import { computed } from 'vue'

import CourseCard from '@/components/course/CourseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useLoader } from '@/composables/useLoader'
import { t, type MessageKey } from '@/i18n'
import { locale } from '@/i18n/locale'
import { useCourseStore } from '@/stores/course'

/** Главная по макету: выбор курса и то, что общее у всех курсов */
const store = useCourseStore()
const { error, loading } = useLoader(
  () => locale.value,
  () => store.loadCourses(),
)
/** Список на текущем языке из стора: обновлённый в фоне сразу заменяет показанный */
const courses = computed(() => store.courses() ?? [])

const FEATURES: { title: MessageKey; text: MessageKey }[] = [
  { title: 'home.accountTitle', text: 'home.accountText' },
  { title: 'home.guestTitle', text: 'home.guestText' },
  { title: 'home.freeTitle', text: 'home.freeText' },
]

function reload() {
  window.location.reload()
}
</script>

<template>
  <main class="home">
    <section class="home__hero">
      <span class="home__badge">{{ t('home.badge') }}</span>
      <h1 class="home__title">{{ t('home.title') }}</h1>
      <p class="home__lead">{{ t('home.lead') }}</p>
    </section>

    <section :aria-label="t('home.coursesLabel')">
      <p v-if="loading && courses.length === 0" class="home__status" role="status">
        {{ t('home.loading') }}
      </p>
      <div v-else-if="error && courses.length === 0" class="home__status" role="alert">
        <p>{{ error.message }}</p>
        <BaseButton variant="ghost" @click="reload">{{ t('home.retry') }}</BaseButton>
      </div>
      <p v-else-if="courses.length === 0" class="home__status">{{ t('home.empty') }}</p>
      <div v-else class="home__courses">
        <CourseCard v-for="course in courses" :key="course.slug" :course="course" />
      </div>
    </section>

    <section class="home__features" :aria-label="t('home.featuresLabel')">
      <div v-for="feature in FEATURES" :key="feature.title" class="home__feature">
        <h2 class="home__feature-title">{{ t(feature.title) }}</h2>
        <p class="home__feature-text">{{ t(feature.text) }}</p>
      </div>
    </section>
  </main>
</template>

<style scoped>
.home {
  max-width: 77.5rem;
  margin: 0 auto;
  padding: var(--space-6) var(--space-6) var(--space-7);
  display: flex;
  flex-direction: column;
  gap: var(--space-7);
}

.home__hero {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
  max-width: 45rem;
}

.home__badge {
  align-self: flex-start;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-pill);
  background: var(--color-primary-soft);
  color: var(--color-accent-text);
  font-weight: 600;
  font-size: var(--font-size-sm);
}

.home__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: clamp(var(--font-size-xxl), 6vw, var(--font-size-display));
  line-height: 1.1;
  text-wrap: balance;
}

.home__lead {
  margin: 0;
  max-width: 35rem;
  font-size: var(--font-size-lg);
  line-height: 1.55;
  color: var(--color-text-muted);
}

.home__courses {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 21rem), 1fr));
  gap: var(--space-5);
}

.home__status {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-3);
  margin: 0;
  color: var(--color-text-muted);
}

.home__status p {
  margin: 0;
}

.home__features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr));
  gap: var(--space-5);
  padding-top: var(--space-6);
  border-top: 1px solid var(--color-border);
}

.home__feature {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.home__feature-title {
  margin: 0;
  font-size: var(--font-size-lg);
}

.home__feature-text {
  margin: 0;
  color: var(--color-text-muted);
  line-height: 1.55;
}
</style>
