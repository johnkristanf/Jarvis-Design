// usePayments.ts
import { computed } from 'vue'
import type { Payment } from '@/types/payment'

export function usePayments(payments: Payment[] | undefined, defaultTotalPrice: number | null) {
    const orderTotalPrice = computed(() => {
        if (defaultTotalPrice) {
            return defaultTotalPrice
        }

        if (!payments || payments.length === 0) return 0
        return payments[0].orders.total_price || 0
    })

    const totalApplied = computed(() => {
        if (!payments) return 0
        return payments.reduce((sum, p) => sum + (p.amount_applied || 0), 0)
    })

    const remainingBalance = computed(() => {
        return orderTotalPrice.value - totalApplied.value
    })

    const hasFullyPaid = computed(() => {
        if (!payments) return false
        return payments.some((p) => p.status === 'fully_paid')
    })

    return { orderTotalPrice, totalApplied, remainingBalance, hasFullyPaid }
}
