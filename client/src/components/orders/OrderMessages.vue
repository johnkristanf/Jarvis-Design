<script lang="ts" setup>
    import { OrderStatus } from '@/types/order'

    defineProps<{
        status: string
        product_name: string | undefined
    }>()
</script>
<template>
    <span v-if="status === OrderStatus.PENDING">
        <span>
            {{ product_name }}
        </span>
        is pending and will be reviewed soon.
    </span>

    <span v-else-if="status === OrderStatus.IN_PROGRESS">
        {{ product_name }} is currently being crafted and prepared. We’ll notify you once it’s ready for delivery or
        pickup.
    </span>

    <span v-else-if="status === OrderStatus.FOR_DELIVERY">
        {{ product_name }} is now out for delivery. Please prepare to receive it.
    </span>

    <span v-else-if="status === OrderStatus.FOR_PICKUP">
        {{ product_name }} is ready for pick-up. Please visit the store to collect it at your scheduled time.
    </span>

    <span v-else-if="status === OrderStatus.COMPLETED">
        {{ product_name }} has been successfully delivered. Thank you for shopping with us!
    </span>
    <span v-else-if="status === OrderStatus.CANCELLED">
        {{ product_name }} has been cancelled. If this was unexpected, please contact support.
    </span>
</template>
