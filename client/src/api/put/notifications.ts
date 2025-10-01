/* eslint-disable @typescript-eslint/no-explicit-any */
import { apiService } from '../axios'

export async function updateNotificationAsRead(notificationData: {
    notification_id: number
    is_admin: boolean
}): Promise<any> {
    return await apiService.put('/api/notification/read', notificationData)
}

export async function updateNotificationAsReadAll(is_admin: boolean): Promise<any> {
    return await apiService.put('/api/all/notification/read', { is_admin })
}
