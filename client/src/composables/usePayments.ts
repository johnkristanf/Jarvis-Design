import { computed, type Ref, type ComputedRef, unref } from 'vue'
import { type Payment } from '@/types/payment'

export function usePayments(
    payments: Ref<Payment[] | undefined> | ComputedRef<Payment[]> | Payment[] | undefined,
) {
    const hasFullyPaid = computed(() => {
        const paymentsValue = unref(payments)

        if (!paymentsValue) return false
        return paymentsValue.some((p) => p.status === 'fully_paid')
    })

    return { hasFullyPaid }
}
