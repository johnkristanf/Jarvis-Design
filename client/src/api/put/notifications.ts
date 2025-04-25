import { apiService } from '../axios'

export async function updateNotificationAsRead(notification_id: number): Promise<any> {
    return await apiService.put('/api/notification/read', { notification_id })
}

export async function updateNotificationAsReadAll(): Promise<any> {
    return await apiService.put('/api/all/notification/read')
}
