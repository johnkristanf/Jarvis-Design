import AdminLayout from '@/layout/AdminLayout.vue'
import DefaultLayout from '@/layout/UserLayout.vue'
import DesignsView from '@/views/users/DesignsView.vue'
import HomeView from '@/views/users/HomeView.vue'
import LoginView from '@/views/users/LoginView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import RegisterView from '@/views/users/RegisterView.vue'
import { createRouter, createWebHistory } from 'vue-router'
import AdminProductsView from '@/views/admin/AdminProductsView.vue'
import AdminDashboardView from '@/views/admin/AdminDashboardView.vue'
import OrdersView from '@/views/users/OrdersView.vue'
import FAQView from '../views/users/FAQView.vue'
import AdminOrdersView from '@/views/admin/AdminOrdersView.vue'
import AdminMatertialsView from '@/views/admin/AdminMatertialsView.vue'
import AdminMessageView from '@/views/admin/AdminMessageView.vue'
import EmailVerificationView from '@/views/users/EmailVerificationView.vue'
import VerifiedEmail from '@/views/users/VerifiedEmail.vue'
import Profile from '@/views/users/Profile.vue'
// import MessageView from '@/views/users/MessageView.vue'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        // CUSTOMER ROUTE
        {
            path: '/',
            component: DefaultLayout,
            children: [
                {
                    path: '',
                    redirect: '/home',
                },

                {
                    path: 'home',
                    component: HomeView,
                },

                {
                    path: 'designs',
                    component: DesignsView,
                },

                {
                    path: 'orders',
                    component: OrdersView,
                },

                // {
                //     path: 'message',
                //     component: MessageView,
                // },

                {
                    path: 'faq',
                    component: FAQView,
                },

                {
                    path: 'auth/login',
                    component: LoginView,
                },

                {
                    path: 'auth/register',
                    component: RegisterView,
                },

                {
                    path: 'email/verification',
                    component: EmailVerificationView,
                },

                {
                    path: 'email/verified',
                    component: VerifiedEmail,
                },

                {
                    path: 'profile',
                    component: Profile,
                },
            ],
        },

        // ADMIN ROUTE

        {
            path: '/admin',
            component: AdminLayout,
            children: [
                {
                    path: 'dashboard',
                    component: AdminDashboardView,
                },

                {
                    path: 'products',
                    component: AdminProductsView,
                },

                {
                    path: 'fabrics',
                    component: AdminMatertialsView,
                },

                {
                    path: 'orders',
                    component: AdminOrdersView,
                },

                {
                    path: 'message',
                    component: AdminMessageView,
                },
            ],
        },

        { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFoundView },
    ],
})

export default router
