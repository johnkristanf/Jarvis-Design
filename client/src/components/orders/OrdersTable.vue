<script lang="ts" setup>
    import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
    import {
        CheckBadgeIcon,
        ChevronDownIcon,
        ShoppingCartIcon,
        TruckIcon,
    } from '@heroicons/vue/20/solid'
    import { getAllOrders, getAllOrderStatus } from '@/api/get/orders'
    import { useAuthorization } from '@/composables/useAuthorization'
    import { formatCurrency, formatDate } from '@/helper/designs'
    import { OrderStatus, type UpdateStatusType } from '@/types/order'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import Loader from '../Loader.vue'
    import { computed, ref } from 'vue'
    import { updateOrderStatus } from '@/api/put/orders'

    const { isAdmin } = useAuthorization()
    const isStatusUpdating = ref<boolean>(false)

    const orderQuery = useQuery({
        queryKey: ['orders'],
        queryFn: getAllOrders,
        enabled: true,
    })

    const statusQuery = useQuery({
        queryKey: ['order_status'],
        queryFn: getAllOrderStatus,
        enabled: true,
    })

    const solutions = [
        {
            name: OrderStatus.DELIVERY,
            icon: TruckIcon,
        },
        {
            name: OrderStatus.PICKUP,
            icon: ShoppingCartIcon,
        },
        {
            name: OrderStatus.COMPLETED,
            icon: CheckBadgeIcon,
        },
    ]

    const iconMap = Object.fromEntries(solutions.map((s) => [s.name, s.icon]))
    const queryClient = useQueryClient()

    const enrichedStatuses = computed(() => {
        if (!statusQuery.data?.value) return []

        return statusQuery.data.value
            .filter((status) => status.name !== 'in_progress')
            .map((status) => ({
                ...status,
                icon: iconMap[status.name] || null,
            }))
    })

    const mutation = useMutation({
        mutationFn: updateOrderStatus,
        onSuccess: async () => {
            queryClient.invalidateQueries({ queryKey: ['orders', 'order_notifications'] })

            try {
                const refetchResult = await orderQuery.refetch()

                if (refetchResult.status === 'success') {
                    isStatusUpdating.value = false
                }
            } catch (err) {
                console.error('Refetch failed:', err)
                isStatusUpdating.value = false
            }
        },

        onError: (error) => {
            console.error('Mutation error:', error)
        },

        onMutate: () => {
            isStatusUpdating.value = true
        },
    })

    const handleStatusChange = (order_id: number, status_id: number, close: () => void) => {
        console.log('Selected order_id:', order_id)
        console.log('Selected status_id:', status_id)

        const statusData: UpdateStatusType = {
            order_id,
            status_id,
        }

        mutation.mutate(statusData)

        close()
    }
</script>

<template>
    <div class="relative h-full overflow-x-auto shadow-md sm:rounded-lg mt-5">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead
                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
            >
                <tr>
                    <th scope="col" class="px-16 py-3">
                        <span>Order ID</span>
                    </th>

                    <th scope="col" class="px-16 py-3">
                        <span>Image Design</span>
                    </th>

                    <th scope="col" class="px-6 py-3">Quantity</th>

                    <th scope="col" class="px-6 py-3">Order Option</th>

                    <th scope="col" class="px-6 py-3">Total Paid Price</th>

                    <th scope="col" class="px-6 py-3 flex items-center">Order Status</th>

                    <th scope="col" class="px-6 py-3">Order Date</th>

                    <th v-if="isAdmin" scope="col" class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="order in orderQuery.data.value"
                    :key="order.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.order_id }}
                    </td>

                    <td class="p-4">
                        <img
                            :src="order.temp_url"
                            class="w-24 max-w-full max-h-full rounded"
                            :alt="order.image_path"
                        />
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.quantity }}
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.order_option.toUpperCase() }}
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ formatCurrency(order.paid_amount.toString()) }}
                    </td>

                    <td class="px-6 py-4">
                        <span
                            :class="{
                                'bg-yellow-100 text-yellow-800 px-2 py-1 rounded':
                                    order.status === OrderStatus.IN_PROGRESS,
                                'bg-sky-100 text-sky-800 px-2 py-1 rounded':
                                    order.status === OrderStatus.DELIVERY,
                                'bg-indigo-100 text-indigo-800 px-2 py-1 rounded':
                                    order.status === OrderStatus.PICKUP,
                                'bg-green-100 text-green-800 px-2 py-1 rounded':
                                    order.status === OrderStatus.COMPLETED,
                            }"
                        >
                            {{ order.status.toUpperCase() }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ formatDate(order.created_at) }}
                    </td>

                    <!-- UPDATE STATUS ACTION BUTTON -->
                    <td
                        v-if="isAdmin"
                        class="relative overflow-visible px-3 py-4 font-semibold text-gray-900 dark:text-white"
                    >
                        <div
                            v-if="order.status !== OrderStatus.COMPLETED"
                            class="w-full max-w-sm px-4"
                        >
                            <Popover v-slot="{ open, close }" class="relative">
                                <PopoverButton
                                    :class="open ? 'text-white' : 'text-white/90'"
                                    class="group hover:opacity-75 hover:cursor-pointer inline-flex items-center rounded-md bg-gray-800 px-3 py-2 text-base font-medium hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75"
                                >
                                    <span>Status</span>
                                    <ChevronDownIcon
                                        :class="open ? 'text-gray-300' : 'text-gray-300/70'"
                                        class="ml-2 h-5 w-5 transition duration-150 ease-in-out group-hover:text-gray-300/80"
                                        aria-hidden="true"
                                    />
                                </PopoverButton>

                                <transition
                                    enter-active-class="transition duration-200 ease-out"
                                    enter-from-class="translate-y-1 opacity-0"
                                    enter-to-class="translate-y-0 opacity-100"
                                    leave-active-class="transition duration-150 ease-in"
                                    leave-from-class="translate-y-0 opacity-100"
                                    leave-to-class="translate-y-1 opacity-0"
                                >
                                    <PopoverPanel
                                        class="absolute z-[9999] left-[-6rem] mt-2 w-[15vw] rounded-lg bg-white shadow-lg ring-1 ring-black/5"
                                    >
                                        <div
                                            class="overflow-hidden z-[9999] rounded-lg shadow-lg ring-1 ring-black/5"
                                        >
                                            <div class="flex flex-col gap-8 bg-white p-7">
                                                <h1
                                                    v-for="item in enrichedStatuses"
                                                    :key="item.name"
                                                    @click="
                                                        handleStatusChange(order.id, item.id, close)
                                                    "
                                                    class="-m-3 hover:cursor-pointer hover:bg-gray-800 hover:text-white flex items-center rounded-lg p-2 transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus-visible:ring focus-visible:ring-orange-500/50"
                                                >
                                                    <div
                                                        class="flex h-10 w-10 shrink-0 items-center justify-center text-white sm:h-12 sm:w-12"
                                                    >
                                                        <component
                                                            :is="item.icon"
                                                            class="h-6 w-6 text-gray-600"
                                                        />
                                                    </div>

                                                    <div class="ml-4">
                                                        <p class="text-sm font-medium">
                                                            {{ item.name.toUpperCase() }}
                                                        </p>
                                                    </div>
                                                </h1>
                                            </div>
                                        </div>
                                    </PopoverPanel>
                                </transition>
                            </Popover>
                        </div>

                        <div v-else>
                            <h1 class="text-green-800 text-center">Already Completed</h1>
                        </div>
                    </td>
                </tr>

                <!-- Empty state message -->
                <tr
                    v-if="
                        orderQuery.data.value &&
                        orderQuery.data.value.length === 0 &&
                        !orderQuery.isLoading.value
                    "
                >
                    <td colspan="7" class="px-6 py-4 text-center">No orders found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="orderQuery.isLoading.value">
        <Loader msg="Loading Orders..." />
    </div>

    <div v-if="isStatusUpdating">
        <Loader msg="Updating Order Status..." />
    </div>
</template>
