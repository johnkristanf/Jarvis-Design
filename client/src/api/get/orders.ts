import type { CartCount, Notifications } from '@/types/order'
import { apiService } from '../axios'

export async function getAllOrderNotifications(): Promise<Notifications[]> {
    const respData = await apiService.get<Notifications[]>('/api/get/order/notifications')
    return respData
}

export async function getAllCartCount(): Promise<number> {
    const respData = await apiService.get<CartCount>('/api/get/cart/count')
    return respData.count
}
