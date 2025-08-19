<script lang="ts" setup>
    import CustomerChatBox from '@/components/message/CustomerChatBox.vue'
    import OrderDetailsModal from '@/components/orders/OrderDetailsModal.vue'
    import { ChatBubbleLeftRightIcon } from '@heroicons/vue/20/solid'
    import { ref, watch } from 'vue'
    import { FwbCard } from 'flowbite-vue'
    import { useQuery } from '@tanstack/vue-query'
    import { getAllOrders } from '@/api/get/orders'
    import Loader from '@/components/Loader.vue'
    import type { Orders } from '@/types/order'

    const isOpenChatBox = ref<boolean>(false)
    const isOrderDetailsOpen = ref<boolean>(false)
    const orderDetails = ref<Orders>()
    const isOrderLoading = ref<boolean>(true)

    const orderQuery = useQuery({
        queryKey: ['orders'],
        queryFn: getAllOrders,
        enabled: true,
    })

    watch(
        () => orderQuery.error,
        (err) => {
            if (err) {
                isOrderLoading.value = false
            }
        },
    )

    const openOrderDetails = (order: Orders) => {
        isOrderDetailsOpen.value = true
        orderDetails.value = order
    }
</script>

<template>
    <div class="card mt-5 p-8">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                Orders & Shipping Details
            </h2>

            <!-- SEARCH INPUT MUST AUTO FETCH ONCHANGE -->
            <div class="flex items-center hover:cursor-pointer hover:opacity-75">
                <label
                    for="default-search"
                    class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white"
                >
                    Search
                </label>
                <div class="relative">
                    <div
                        class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none"
                    >
                        <svg
                            class="w-4 h-4 text-gray-500 dark:text-gray-400"
                            aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 20 20"
                        >
                            <path
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                            />
                        </svg>
                    </div>
                    <input
                        type="search"
                        id="default-search"
                        class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Search Order No., Status..."
                        required
                    />
                </div>
            </div>
        </div>

        <!-- FLOATING MESSAGE ICON -->
        <div
            class="fixed bottom-13 right-11 bg-gray-800 rounded-full z-[9999] p-3 hover:cursor-pointer hover:opacity-75"
            @click="isOpenChatBox = true"
        >
            <ChatBubbleLeftRightIcon class="size-10 text-white" />
        </div>

        <div v-if="isOpenChatBox">
            <CustomerChatBox :isOpen="isOpenChatBox" @close="isOpenChatBox = false" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 mt-5 pb-10 gap-5">
            <fwb-card
                v-for="order in orderQuery.data.value"
                :key="order.id"
                :img-alt="order.name"
                :img-src="order.temp_url"
                variant="image"
                class="w-xs hover:opacity-75 hover:cursor-pointer"
                @click="() => openOrderDetails(order)"
            >
                <div class="p-5">
                    <p class="font-semibold text-gray-700 dark:text-gray-400">
                        {{ order.order_number }}
                    </p>
                </div>
            </fwb-card>

        </div>

        <OrderDetailsModal
            v-if="orderDetails"
            :isOpen="isOrderDetailsOpen"
            :orderDetails="orderDetails"
            @close="isOrderDetailsOpen = false"
        />
        <Loader v-if="orderQuery.isLoading.value && isOrderLoading" msg="Loading Orders..." />
    </div>
</template>
