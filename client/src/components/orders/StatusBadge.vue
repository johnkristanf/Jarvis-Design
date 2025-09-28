<script lang="ts" setup>
    import { OrderStatus } from '@/types/order'

    defineProps<{
        status: string
    }>()
</script>

<template>
    <span
        :class="{
            'bg-yellow-100 text-yellow-800 px-2 py-1 rounded': status === OrderStatus.PENDING,
            'bg-orange-100 text-orange-800 px-2 py-1 rounded': status === OrderStatus.IN_PROGRESS,
            'bg-indigo-100 text-indigo-800 px-2 py-1 rounded': status === OrderStatus.FOR_PICKUP,
            'bg-blue-100 text-blue-800 px-2 py-1 rounded': status === OrderStatus.FOR_DELIVERY,
            'bg-green-100 text-green-800 px-2 py-1 rounded': status === OrderStatus.COMPLETED,
            'bg-red-100 text-red-800 px-2 py-1 rounded': status === OrderStatus.CANCELLED,
        }"
    >
        {{ status.toUpperCase() }}
    </span>
</template>
