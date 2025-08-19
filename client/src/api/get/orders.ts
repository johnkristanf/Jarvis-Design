import type { Notifications, Orders, OrderStatusType } from '@/types/order'
import { apiService } from '../axios'

export async function getAllOrders(): Promise<Orders[]> {
    const respData = await apiService.get<Orders[]>('/api/get/orders');
    console.log("respData orders: ", respData);
    
    return respData
}

export async function getAllOrderStatus(): Promise<OrderStatusType[]> {
    const respData = await apiService.get<OrderStatusType[]>('/api/get/order/status')
    return respData
}

export async function getAllOrderNotifications(): Promise<Notifications[]> {
    const respData = await apiService.get<Notifications[]>('/api/get/order/notifications')
    return respData
}
