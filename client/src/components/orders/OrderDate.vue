<script lang="ts" setup>
    import { formatDate } from '@/helper/designs'
    import { OrderStatus } from '@/types/order'

    defineProps<{
        delivery_date: string | null
        status: string
    }>()
</script>
<template>
    <span v-if="delivery_date && [OrderStatus.FOR_PICKUP, OrderStatus.FOR_DELIVERY].includes(status)" >
        {{ status === OrderStatus.FOR_PICKUP ? 'Pick Up Date:' : 'Delivery Date:' }}
        <span :class="status === OrderStatus.FOR_PICKUP ? 'px-2 py-1 bg-indigo-100 text-indigo-600' : 'px-2 py-1 bg-blue-100 text-blue-600'">
            {{ formatDate(delivery_date) }}
        </span>
    </span>
</template>
