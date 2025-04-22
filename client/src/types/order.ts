type OrderStatus = 'in_progress' | 'pick_up' | 'delivery' | 'completed';

export const OrderStatus = {
    IN_PROGRESS: 'in_progress',
    PICKUP: 'pick_up',
    DELIVERY: 'delivery',
    COMPLETED: 'completed',
} as const;


export type OrderTypes = 'uploaded' | 'pre-made' ;

export const OrderTypes = {
    UPLOADED: 'uploaded',
    PRE_MADE: 'pre-made',
} as const;


export type OrderOptions = 'delivery' | 'pick_up' ;

export const OrderOptions = {
    DELIVERY: 'delivery',
    PICK_UP: 'pick_up',
} as const;


export type Orders = {
    id: number;
    order_id: string;
    order_option: string;
    paid_amount: string; 
    quantity: number;
    created_at: string; 
    status: string;
    name: string;
    image_path: string;
    temp_url: string;
};

export type OrderStatusType = {
    id: number,
    name: string
}
  

export type UpdateStatusType = {
    order_id: number,
    status_id: number
}
  

export type Notifications = {
    id: number;
    order_id: string;
    is_read: boolean;
    status: OrderStatus 
    created_at: string;
};