import { ref } from 'vue'

export type OrderNavState = {
    orderId: number
    scrollTo?: string
} | null

// Module-level reactive ref shared between NavBar and OrdersView
export const pendingOrderNav = ref<OrderNavState>(null)
