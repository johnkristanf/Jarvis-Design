type OrderStatus = 'in_progress' | 'pickup' | 'delivery' | 'completed';

export const OrderStatus = {
    IN_PROGRESS: 'in_progress',
    PICKUP: 'pickup',
    DELIVERY: 'delivery',
    COMPLETED: 'completed',
} as const;