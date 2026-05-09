import type { FeedbackResponse } from '@/types/feedback'
import { apiService } from '../axios'

export async function getAllFeedbacks(): Promise<FeedbackResponse[]> {
    return await apiService.get('/api/get/feedbacks')
}
