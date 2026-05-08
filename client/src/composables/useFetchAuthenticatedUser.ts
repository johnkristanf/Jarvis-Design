/* eslint-disable @typescript-eslint/no-explicit-any */
import { fetchUserData } from '@/api/get/user-data'
import { useAuthStore } from '@/stores/user'
import { useQuery } from '@tanstack/vue-query'
import { watch } from 'vue'

export const useFetchAuthenticatedUser = () => {
    const authStore = useAuthStore()

    const { isLoading, data, error, refetch } = useQuery({
        queryKey: ['authenticatedUser'],
        queryFn: fetchUserData,
        retry: false, // Don't keep retrying if they are just a guest
    })

    watch(
        data,
        (newData) => {
            if (newData) {
                authStore.setUser(newData)
                authStore.setAuthenticated(true)
            }
        },
        { immediate: true }
    )

    watch(
        error,
        (newError: any) => {
            if (newError) {
                console.error('Error fetching user data: ', newError)

                if (newError.statusCode === 401) {
                    authStore.setAuthenticated(false);
                    authStore.setUser(undefined);
                }
            }
        },
        { immediate: true }
    )

    return {
        authStore,
        isLoading,
        refetchUser: refetch,
    }
}
