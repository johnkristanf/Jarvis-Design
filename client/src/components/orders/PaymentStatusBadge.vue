<script lang="ts" setup>
    import { getPaymentStatusBadge, getPaymentStatusLabel } from '@/helper/payment'
    import type { PaymentStatus } from '@/types/payment'

    defineProps<{
        status: PaymentStatus
    }>()
</script>

<template>
    <span :class="getPaymentStatusBadge(status)" class="px-3 py-1 rounded-md text-xs font-medium border">
        {{ getPaymentStatusLabel(status) }}
    </span>
</template>
