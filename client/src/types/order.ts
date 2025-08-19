import type { User } from './user'

type OrderStatus = 'in_progress' | 'pick_up' | 'delivery' | 'completed'

export const OrderStatus = {
    PENDING: 'pending',
    CANCELLED: 'cancelled',
    APPROVED: 'completed',
}

export type OrderTypes = 'uploaded' | 'pre-made'

export const OrderTypes = {
    UPLOADED: 'uploaded',
    PRE_MADE: 'pre-made',
} as const

export type OrderOptions = 'delivery' | 'pick_up'

export const OrderOptions = {
    DELIVERY: 'delivery',
    PICK_UP: 'pick_up',
} as const

export type SelectedOrderOption = {
    id: number
    name: OrderOptions
    tag: string
}

export type QrCodePaymentData = {
    product_name: string
    total_quantity: number
    total_price: number
}

export interface SizePivot {
    order_id: number
    size_id: number
    quantity: number
    created_at: string
    updated_at: string
}

export interface Size {
    id: number
    name: string
    created_at: string
    updated_at: string
    pivot: SizePivot
}

export type Orders = {
    id: number
    order_number: string
    color: string
    phone_number: string
    address: string
    design_id: number
    order_option: string
    paid_amount: string
    quantity: number
    solo_quantity: number
    total_price: number
    created_at: string
    status: string
    name: string
    image_path: string
    temp_url: string
    delivery_date: string | null

    user?: User
    sizes: Size[]
}

export type OrderStatusType = {
    id: number
    name: string
}

export type UpdateStatusType = {
    order_id: number
    status: string
}

export type Notifications = {
    id: number
    order_id: string
    is_read: boolean
    status: OrderStatus
    created_at: string
}

export type OrderLogs = {
    id: number
    material_name: string
    order_id: number
    orders: {
        id: number
        order_id: string
    }
    total_quantity_used: number
    unit: string
    created_at: string
    updated_at: string
    user_id: number
    users: {
        id: number
        name: string
    }
}

export type PlaceOrderData = {
    order_type: string
    design_id: number
    total_price: number
    order_option: string
    quantity: number
    color_id: number
    size_id: number
}
