<script lang="ts" setup>
    import OrderLogsTable from '@/components/orders/OrderLogsTable.vue'
    import OrdersTable from '@/components/orders/OrdersTable.vue'
    import { ArrowLeftIcon, ArrowRightIcon } from '@heroicons/vue/20/solid'
    import { ref } from 'vue'

    const isOrderLogsOpen = ref<boolean>(false)
</script>

<template>
    <div class="w-full h-[120vh] p-4 rounded-md bg-gray-100 border-1 border-gray-400 mb-16">
        <h1 class="text-2xl">Customer Orders</h1>
        <p class="text-sm text-gray-400 mt-1 mb-12">
            Displays all customer orders, their statuses, and essential order details for tracking
            and management.
        </p>

        <OrdersTable  />
    </div>
</template>
