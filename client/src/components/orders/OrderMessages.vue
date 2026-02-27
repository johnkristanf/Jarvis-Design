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
            Your order
        </span>
        is pending and will be reviewed soon.
    </span>

    <span v-else-if="status === OrderStatus.IN_PROGRESS">
        Your order is currently being crafted and prepared. We’ll notify you once it’s ready
        for delivery or pickup.
    </span>

    <span v-else-if="status === OrderStatus.FOR_DELIVERY">
        Your order is now out for delivery. Please prepare to receive it.
    </span>

    <span v-else-if="status === OrderStatus.FOR_PICKUP">
        Your order is ready for pick-up. Please visit the store to collect it at your
        scheduled time.
    </span>

    <span v-else-if="status === OrderStatus.COMPLETED">
        Your order has been successfully delivered. Thank you for shopping with us!
    </span>
    <span v-else-if="status === OrderStatus.CANCELLED">
        Your order has been cancelled. If this was unexpected, please contact support.
    </span>

    <span v-else-if="status === OrderStatus.PAYMENT_UPDATED">
        Payment details for your order have been updated. Please check the payment history.
    </span>
</template>
