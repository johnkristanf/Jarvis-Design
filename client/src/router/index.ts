import AdminLayout from '@/layout/AdminLayout.vue'
import DefaultLayout from '@/layout/UserLayout.vue'
import DesignsView from '@/views/users/DesignsView.vue'
import HomeView from '@/views/users/HomeView.vue'
import LoginView from '@/views/users/LoginView.vue'
import ForgotPasswordView from '@/views/users/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/users/ResetPasswordView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import RegisterView from '@/views/users/RegisterView.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/user'
import { fetchUserData } from '@/api/get/user-data'
import { UserRole } from '@/types/user'
import AdminProductsView from '@/views/admin/AdminProductsView.vue'
import AdminDashboardView from '@/views/admin/AdminDashboardView.vue'
import OrdersView from '@/views/users/OrdersView.vue'
import FAQView from '../views/users/FAQView.vue'
import AdminOrdersView from '@/views/admin/AdminOrdersView.vue'
import AdminMatertialsView from '@/views/admin/AdminMatertialsView.vue'
import AdminMessageView from '@/views/admin/AdminMessageView.vue'
import EmailVerificationView from '@/views/users/EmailVerificationView.vue'
import VerifiedEmail from '@/views/users/VerifiedEmail.vue'
import AccountProfile from '@/views/users/AccountProfile.vue'
import AdminAccountProfileView from '@/views/admin/AdminAccountProfileView.vue'
import ShoppingCartView from '@/views/users/ShoppingCartView.vue'
import AdminReportsView from '@/views/admin/AdminReportsView.vue'

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
                    meta: { requiresAuth: true, requiresUser: true },
                },

                {
                    path: 'orders/cart',
                    component: ShoppingCartView,
                    meta: { requiresAuth: true, requiresUser: true },
                },

                {
                    path: 'faq',
                    component: FAQView,
                },

                {
                    path: 'auth/login',
                    component: LoginView,
                    meta: { requiresGuest: true },
                },

                {
                    path: 'auth/register',
                    component: RegisterView,
                    meta: { requiresGuest: true },
                },

                {
                    path: 'auth/forgot-password',
                    component: ForgotPasswordView,
                    meta: { requiresGuest: true },
                },

                {
                    path: 'auth/reset-password',
                    component: ResetPasswordView,
                    meta: { requiresGuest: true },
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
                    component: AccountProfile,
                    meta: { requiresAuth: true, requiresUser: true },
                },
            ],
        },

        // ADMIN ROUTE
        {
            path: '/admin',
            component: AdminLayout,
            meta: { requiresAuth: true, requiresAdmin: true },
            children: [
                {
                    path: 'dashboard',
                    component: AdminDashboardView,
                },

                {
                    path: 'reports',
                    component: AdminReportsView,
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
                    path: 'message/:id?',
                    component: AdminMessageView,
                },

                {
                    path: 'profile',
                    component: AdminAccountProfileView,
                },
            ],
        },

        { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFoundView },
    ],
})

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()

    // Eagerly fetch user on first load if we don't know the auth state
    if (!authStore.isInitialized) {
        try {
            const data = await fetchUserData()
            authStore.setAuthenticated(true)
            authStore.setUser(data)
        } catch (error) {
            authStore.setAuthenticated(false)
            authStore.setUser(undefined)
        } finally {
            authStore.setIsInitialized(true)
        }
    }

    // Navigation Guards
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return next('/auth/login')
    }

    if (to.meta.requiresAdmin && authStore.currentUser?.role?.name !== UserRole.ADMIN) {
        return next('/')
    }

    if (to.meta.requiresUser && authStore.currentUser?.role?.name === UserRole.ADMIN) {
        return next('/admin/dashboard')
    }

    if (to.meta.requiresGuest && authStore.isAuthenticated) {
        if (authStore.currentUser?.role?.name === UserRole.ADMIN) {
            return next('/admin/dashboard')
        }
        return next('/')
    }

    next()
})

export default router
