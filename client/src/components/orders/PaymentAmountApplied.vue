<script lang="ts" setup>
import type { PaymentStatus } from '@/types/payment';

    defineProps<{
        amount: number
        status: PaymentStatus
    }>()
</script>

<template>
    <div v-if="amount > 0" class="flex items-center justify-between bg-gray-50 p-3 rounded mb-3">
        <span class="text-sm text-gray-600">Amount Applied</span>
        <span class="text-lg font-bold text-black">₱ {{ Math.floor(amount).toLocaleString() }}</span>
    </div>

    <!-- IF THE AMOUNT IS 0 INFORM THE USER THAT THE PAYMENT IS CURRENT UNDER ADMIN REVIEW -->
    <div v-else class="bg-amber-50 border border-amber-200 p-3 rounded mb-3">
        <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                />
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-amber-800">Payment Under Review</p>
                <p class="text-xs text-amber-700 mt-1">
                    This payment is currently being validated by the administrator. The amount will be updated once verified.
                </p>
            </div>
        </div>
    </div>
</template>
