<script lang="ts" setup>
    import OrderLogsTable from '@/components/orders/OrderLogsTable.vue'
    import OrdersTable from '@/components/orders/OrdersTable.vue'
    import { ArrowLeftIcon, ArrowRightIcon } from '@heroicons/vue/20/solid'
    import { ref } from 'vue'

    const isOrderLogsOpen = ref<boolean>(false)
</script>

<template>
    <div class="w-full h-[70vh] p-4 rounded-md bg-gray-100 border-1 border-gray-400 mb-16">
        <h1 class="text-2xl">Customer Orders</h1>
        <p class="text-sm text-gray-400 mt-1">
            Displays all customer orders, their statuses, and essential order details for tracking
            and management.
        </p>

        <div class="py-3 flex justify-end">
            <h1
                v-if="!isOrderLogsOpen"
                @click="isOrderLogsOpen = true"
                class="text-gray-400 flex gap-1 hover:cursor-pointer hover:opacity-75"
            >
                Logs
                <ArrowRightIcon class="size-6" />
            </h1>

            <h1
                v-else
                @click="isOrderLogsOpen = false"
                class="text-gray-400 flex gap-1 hover:cursor-pointer hover:opacity-75"
            >
                <ArrowLeftIcon class="size-6" />
                Orders
            </h1>
        </div>

        <OrderLogsTable v-if="isOrderLogsOpen" />
        <OrdersTable v-else />
    </div>
</template>
