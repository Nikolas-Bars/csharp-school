import { createMemoryHistory, createRouter } from 'vue-router'

/** Роутер для тестов: те же имена маршрутов, что в src/router, страницы — пустые */
export async function testRouter(path = '/') {
  const page = { template: '<div />' }
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: page },
      { path: '/login', name: 'login', component: page },
      { path: '/register', name: 'register', component: page },
      { path: '/courses/:course', name: 'course', component: page },
      { path: '/courses/:course/:module/:lesson', name: 'lesson', component: page },
    ],
  })
  await router.push(path)
  return router
}
