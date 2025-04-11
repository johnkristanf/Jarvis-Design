import DefaultLayout from '@/layout/DefaultLayout.vue'
import DesignsView from '@/views/DesignsView.vue'
import HomeView from '@/views/HomeView.vue'
import LoginView from '@/views/LoginView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import RegisterView from '@/views/RegisterView.vue'
import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [

    {
      path: '/',
      component: DefaultLayout,
      children: [

        {
          path: '',
          redirect: '/home'
        },

        {
          path: 'home',
          component: HomeView
        },

        {
          path: 'designs',
          component: DesignsView
        },


        {
          path: 'auth/login',
          component: LoginView
        },

        {
          path: 'auth/register',
          component: RegisterView
        },
      ]
    },

    { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFoundView },

  ],
})

export default router
