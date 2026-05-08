import { logoutUser } from '@/api/post/logout'
import type { AuthenticatedUserData } from '@/types/user'
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        isAuthenticated: false,
        isLoggingOut: false,
        isInitialized: false,
        user: undefined as AuthenticatedUserData | undefined,
    }),

    getters: {
        isLogginedIn: (state) => state.isAuthenticated,
        LoggingOut: (state) => state.isLoggingOut,
        currentUser: (state) => state.user,
    },
    actions: {
        setUser(userData: AuthenticatedUserData | undefined) {
            this.user = userData
        },

        setAuthenticated(value: boolean) {
            this.isAuthenticated = value
        },

        setIsInitialized(value: boolean) {
            this.isInitialized = value
        },

        async logout() {
            this.isLoggingOut = true

            try {
                await logoutUser()
                this.setAuthenticated(false)
                this.setUser(undefined)
            } catch (error) {
                console.error('Logout failed:', error)
            } finally {
                this.isLoggingOut = false
            }
        },
    },
})
