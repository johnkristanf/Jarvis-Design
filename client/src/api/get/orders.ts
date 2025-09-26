import type { Notifications } from '@/types/order'
import { apiService } from '../axios'

export async function getAllOrderNotifications(): Promise<Notifications[]> {
    const respData = await apiService.get<Notifications[]>('/api/get/order/notifications')
    return respData
}
