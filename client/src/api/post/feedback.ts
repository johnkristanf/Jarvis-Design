import type { AxiosResponse } from 'axios'
import { apiService } from '../axios'
import type { FeedbackData } from '@/types/feedback'

export async function submitFeedbackApi(data: FeedbackData): Promise<AxiosResponse> {
    return await apiService.post('/api/feedback', data)
}
