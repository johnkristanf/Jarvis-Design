import { useAuthStore } from '@/stores/user'
import { UserRole } from '@/types/user'
import { computed } from 'vue'

export function useAuthorization() {
    const authStore = useAuthStore()

    const isAdmin = computed(() => {
        const role = authStore.currentUser?.role?.name
        return !!role && role === UserRole.ADMIN
    })

    const isUser = computed(() => {
        const role = authStore.currentUser?.role?.name
        return !!role && role === UserRole.USER
    })

    return {
        isAdmin,
        isUser,
    }
}
