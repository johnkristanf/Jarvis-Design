type OrderStatus = 'in_progress' | 'pickup' | 'delivery' | 'completed';

export const OrderStatus = {
    IN_PROGRESS: 'in_progress',
    PICKUP: 'pickup',
    DELIVERY: 'delivery',
    COMPLETED: 'completed',
} as const;


export type OrderTypes = 'uploaded' | 'pre-made' ;

export const OrderTypes = {
    UPLOADED: 'uploaded',
    PRE_MADE: 'pre-made',
} as const;