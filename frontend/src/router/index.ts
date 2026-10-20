import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    /** Только для гостя: вошедшего ученика уводим на главную (вход, регистрация, смена пароля) */
    guestOnly?: boolean
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    {
      path: '/courses/:course',
      name: 'course',
      component: () => import('@/views/CourseMapView.vue'),
    },
    {
      path: '/courses/:course/:module/:lesson',
      name: 'lesson',
      component: () => import('@/views/LessonView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/views/ForgotPasswordView.vue'),
      meta: { guestOnly: true },
    },
    // Сюда ведёт ссылка из письма: ?token=…&email=… (адрес собирает бэкенд, app.frontend_url)
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('@/views/ResetPasswordView.vue'),
      meta: { guestOnly: true },
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

router.beforeEach(async (to) => {
  if (!to.meta.guestOnly) {
    return true
  }
  const auth = useAuthStore()
  await auth.restore()
  return auth.isLoggedIn ? { name: 'home' } : true
})

export default router
