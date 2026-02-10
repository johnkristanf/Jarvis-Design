<script lang="ts" setup>
import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
import { useQuery } from '@tanstack/vue-query'
import { apiService } from '@/api/axios'
import type { Products } from '@/types/product'
import { useRouter } from 'vue-router'

const { authStore } = useFetchAuthenticatedUser()
const router = useRouter()

const {
    data: products,
    isLoading,
    isError,
} = useQuery({
    queryKey: ['home-products-with-designs'],
    queryFn: async () => {
        return await apiService.get<Products[]>(`/api/get/products/with-designs?limit=3`)
    },
})

const handleGoToDesigns = () => {
    router.push('/designs')
}
</script>

<template>
    <div class="bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mx-auto py-16 sm:py-24">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <h2 class="text-5xl font-bold text-gray-900">Designs</h2>
                        <h2 class="text-md text-gray-500 mb-6">
                            PM us now for orders. We have big discounts and freebies!
                        </h2>
                    </div>

                    <router-link
                        v-if="authStore.currentUser"
                        to="/designs"
                        class="flex hover:cursor-pointer opacity-75 text-lg"
                    >
                        <h1 class="text-gray-900 hover:opacity-40">View All</h1>
                    </router-link>
                </div>

                <div v-if="isLoading" class="py-10 text-gray-500">Loading designs...</div>
                <div v-else-if="isError" class="py-10 text-red-600">Failed to load designs.</div>

                <div v-else class="space-y-12 lg:grid lg:grid-cols-3 lg:space-y-0 lg:gap-x-6">
                    <div
                        v-for="product in products"
                        :key="product.id"
                        class="group relative hover:cursor-pointer"
                        @click="handleGoToDesigns"
                    >
                        <img
                            :src="product.design_images?.[0]?.temp_url || '/jersey-1.jpg'"
                            :alt="product.name"
                            class="w-full rounded-lg bg-white object-cover group-hover:opacity-75 max-sm:h-80 sm:aspect-2/1 lg:aspect-square"
                        />
                        <h3 class="mt-6 text-sm text-gray-500">
                            {{ product.name }}
                        </h3>
                        <p class="text-base font-semibold text-gray-900">₱ {{ product.unit_price }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
