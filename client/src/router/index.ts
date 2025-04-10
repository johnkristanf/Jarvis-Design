import DefaultLayout from '@/layout/DefaultLayout.vue'
import HomeView from '@/views/HomeView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [

    {
      path: '/',
      component: DefaultLayout,
      children: [
        {
          path: 'home',
          component: HomeView
        },

        {
          path: '',
          redirect: '/home'
        }
      ]
    },

    { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFoundView },

  ],
})

export default router
