<script setup lang="ts">
    import { ref } from 'vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { useToast } from 'primevue/usetoast'
    import { apiService } from '@/api/axios'
    import type { Orders } from '@/types/order'

    const props = defineProps<{
        order: Orders
        currentTotalPaid: number
    }>()

    const emit = defineEmits<{
        (e: 'close'): void
    }>()

    const toast = useToast()
    const queryClient = useQueryClient()

    const newRemainingBalance = ref<number | null>(null)

    const updateDiscountMutation = useMutation({
        mutationFn: async ({
            order_id,
            discount_amount,
        }: {
            order_id: number
            discount_amount: number
        }) => {
            return await apiService.put('/api/update/order/discount', {
                order_id,
                discount_amount,
            })
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Remaining Balance Updated',
                life: 1500,
            })
            newRemainingBalance.value = null
            emit('close')

            queryClient.invalidateQueries({ queryKey: ['orders'] })
            queryClient.invalidateQueries({ queryKey: ['payments_by_order', props.order.id] })
        },
        onError: (error) => {
            console.error('Error updating balance:', error)
            toast.add({
                severity: 'error',
                summary: 'Failed to update balance',
                detail: 'Please try again',
                life: 3000,
            })
        },
    })

    const submitNewBalance = () => {
        if (newRemainingBalance.value === null || newRemainingBalance.value < 0) {
            toast.add({
                severity: 'warn',
                summary: 'Invalid Balance',
                detail: 'Please enter a valid amount',
                life: 2000,
            })
            return
        }

        const oldRemainingBalance = props.order.total_price - props.currentTotalPaid
        const discountAmount = Math.max(0, oldRemainingBalance - Number(newRemainingBalance.value))

        updateDiscountMutation.mutate({ order_id: props.order.id, discount_amount: discountAmount })
    }
</script>

<template>
    <div class="bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Edit Remaining Balance
        </h3>
        <div class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    New Remaining Balance (₱)
                </label>
                <input
                    v-model="newRemainingBalance"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="Enter new remaining balance"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    @keypress.enter="submitNewBalance"
                />
            </div>
            <button
                @click="submitNewBalance"
                :disabled="updateDiscountMutation.isPending.value || newRemainingBalance === null"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed h-[42px]"
            >
                <span v-if="updateDiscountMutation.isPending.value">Processing...</span>
                <span v-else>Confirm New Balance</span>
            </button>
        </div>
    </div>
</template>
