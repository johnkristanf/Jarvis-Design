import type { UpdateProfilePayload } from '@/types/user'
import { apiService } from '../axios'

export async function updateProfile(data: UpdateProfilePayload) {
    return await apiService.post('/api/update/profile', data)
}
