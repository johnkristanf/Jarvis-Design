import type { AxiosResponse } from 'axios'
import { apiService } from '../axios'

export async function sendChatMessageApi(formData: FormData): Promise<AxiosResponse> {
    return await apiService.post('/api/send/chat', formData)
}
