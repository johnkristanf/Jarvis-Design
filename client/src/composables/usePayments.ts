import { computed, type Ref, type ComputedRef, unref } from 'vue'
import type { Payment } from '@/types/payment'

export function usePayments(
    payments: Ref<Payment[] | undefined> | ComputedRef<Payment[]> | Payment[] | undefined,
    defaultTotalPrice: number | null
) {
    const orderTotalPrice = computed(() => {
        if (defaultTotalPrice) {
            return defaultTotalPrice
        }

        // Use unref to unwrap ref/computed or return the value directly
        const paymentsValue = unref(payments)
        
        if (!paymentsValue || paymentsValue.length === 0) return 0
        return paymentsValue[0].orders.total_price || 0
    })

    const totalApplied = computed(() => {
        const paymentsValue = unref(payments)
        
        if (!paymentsValue) return 0
        return paymentsValue.reduce((sum, p) => sum + (p.amount_applied || 0), 0)
    })

    const remainingBalance = computed(() => {
        return orderTotalPrice.value - totalApplied.value
    })

    const hasFullyPaid = computed(() => {
        const paymentsValue = unref(payments)
        
        if (!paymentsValue) return false
        return paymentsValue.some((p) => p.status === 'fully_paid')
    })

    return { orderTotalPrice, totalApplied, remainingBalance, hasFullyPaid }
}