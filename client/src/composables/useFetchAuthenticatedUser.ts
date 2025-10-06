/* eslint-disable @typescript-eslint/no-explicit-any */
import { fetchUserData } from '@/api/get/user-data'
import { useAuthStore } from '@/stores/user'
import { onMounted, ref } from 'vue'

export const useFetchAuthenticatedUser = () => {
    const isLoading = ref()
    const authStore = useAuthStore()

    const userDataFetch = async () => {
        isLoading.value = true

        try {
            const fetchedUserData = await fetchUserData()
            authStore.setUser(fetchedUserData)
            authStore.setAuthenticated(true)
        } catch (error: any) {
            console.error("Error fetching user data: ", error);
        } finally {
            isLoading.value = false
        }
    }

    onMounted(() => {
        userDataFetch()
    })

    return {
        authStore,
        isLoading,
        refetchUser: userDataFetch,
    }
}
