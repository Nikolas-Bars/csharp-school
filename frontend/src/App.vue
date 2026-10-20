<script setup lang="ts">
import { RouterView } from 'vue-router'

import AppHeader from '@/components/layout/AppHeader.vue'
import { useThemeStore } from '@/stores/theme'

// Стор применяет тему к <html> сразу при создании
useThemeStore()
</script>

<template>
  <AppHeader />
  <RouterView />
</template>
