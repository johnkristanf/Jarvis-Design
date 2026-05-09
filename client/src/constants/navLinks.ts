import type { Router } from 'vue-router'

export interface NavLink {
    name: string
    to: string
}

export interface DropdownNavLink {
    name: string
    onclick: () => void
}

export const navigation: NavLink[] = [
    { name: 'Home', to: '/home' },
    { name: 'Designs', to: '/designs' },
    { name: 'Orders', to: '/orders' },
    { name: 'FAQ', to: '/faq' },
]

export const authNavigation: NavLink[] = [
    { name: 'Login', to: '/auth/login' },
    { name: 'Register', to: '/auth/register' },
]

// Factory function — needs router + authStore at runtime
export const getNavbarDropdownLinks = (
    router: Router,
    authStore: { logout: () => Promise<void> },
): DropdownNavLink[] => [
    {
        name: 'Your Profile',
        onclick: () => router.push('/profile'),
    },
    {
        name: 'Sign Out',
        onclick: async () => {
            await authStore.logout()
            window.location.href = '/'
        },
    },
]
