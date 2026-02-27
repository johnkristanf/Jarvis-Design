import type { Payment } from './payment'
import type { User } from './user'

type OrderStatus = 'in_progress' | 'pick_up' | 'delivery' | 'completed'

export const OrderStatus = {
    CANCELLED: 'cancelled',
    PENDING: 'pending',
    IN_PROGRESS: 'in_progress',
    FOR_DELIVERY: 'for_delivery',
    FOR_PICKUP: 'for_pickup',
    PAYMENT_UPDATED: 'payment_updated',
    COMPLETED: 'completed',
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
    order_id?: number
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

export interface OrderItem {
    id: number
    order_id: number
    product_id: number
    color: string
    product_unit_price: number
    total_quantity: number
    total_price: number
    own_design_url?: string
    business_design_url?: string
    solo_quantity?: number
    fabric_type_id?: number
    created_at: string
    updated_at: string
    product?: Product
    sizes: Size[]
    temp_url?: string
}

export type Orders = {
    id: number
    order_number: string
    phone_number: string
    address: string
    order_option: string
    total_price: number
    total_paid: number
    balance: number
    created_at: string
    status: string
    delivery_date: string | null
    order_payments: Payment[]
    user?: User
    items: OrderItem[]
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
    orders: Orders
    status: OrderStatus
    created_at: string
}

export type CartCount = {
    count: number
}

export type AdminNotifications = {
    id: number
    type: string
    message: string
    is_read: boolean
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

export interface Product {
    id: number
    name: string
}

export interface LatestOrders {
    id: number
    order_number: string
    status: string
    items: OrderItem[]
}

export type CardAnalytics = {
    total_sales: string
    total_customers: number
    total_pending_orders: number
    total_completed_orders: number
}

export interface FabricType {
    id: number
    name: string
    unit: string
    quantity: number
    reorder_level: number
    created_at: string
    updated_at: string
}
export interface Design {
    id: number
    image_url: string
    product_id: number
    created_at: string | null
    updated_at: string | null
}

export interface ProductDetails {
    id: number
    color: string
    name: string
    category_id: number
    unit_price: string
    fabric_quantity: string
    fabric_type_id: number
    created_at: string
    updated_at: string
    deleted_at: string | null
    designs: Design[]
    size: Size

    desired_quantity?: number
    own_design_url?: string
    own_design_temp_url?: string
}

export interface UserBasic {
    id: number
    name: string
    email: string
    address: string | null
    phone_number: string | null
    created_at: string
    updated_at: string
    username: string
    role_id: number
    prompt_limit: number
}

export interface CartItem {
    id: number
    color: string
    created_at: string
    updated_at: string
    product_id: number
    fabric_type_id: number
    fabric_types: FabricType
    product: ProductDetails
    size_id: number
    size: Size
    quantity: number
    user_id: number
    user: UserBasic
    own_design_url?: string
    own_design_temp_url?: string
}

export enum OrderAction {
    ADD_TO_CART = 'add_to_cart',
    BUY_NOW = 'buy_now',
}
