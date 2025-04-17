import AdminLayout from '@/layout/AdminLayout.vue'
import DefaultLayout from '@/layout/UserLayout.vue'
import DesignsView from '@/views/users/DesignsView.vue'
import HomeView from '@/views/users/HomeView.vue'
import LoginView from '@/views/users/LoginView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import RegisterView from '@/views/users/RegisterView.vue'
import { createRouter, createWebHistory } from 'vue-router'
import AdminDesignsView from '@/views/admin/AdminDesignsView.vue'
import AdminDashboardView from '@/views/admin/AdminDashboardView.vue'
import OrdersView from '@/views/users/OrdersView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [

    // USER ROUTE
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
          path: 'orders',
          component: OrdersView
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



    // ADMIN ROUTE


    {
      path: '/admin',
      component: AdminLayout,
      children: [

        {
          path: 'dashboard',
          component: AdminDashboardView
        },

        {
          path: 'designs',
          component: AdminDesignsView
        },

      ]
    },

    { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFoundView },

  ],
})

export default router
