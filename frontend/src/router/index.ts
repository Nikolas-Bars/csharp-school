import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    // Служебная страница проверки runner — только при разработке, в сборку не попадает
    ...(import.meta.env.DEV
      ? [
          {
            path: '/dev/runner',
            name: 'dev-runner',
            component: () => import('@/views/dev/RunnerPlaygroundView.vue'),
          },
        ]
      : []),
  ],
})

export default router
