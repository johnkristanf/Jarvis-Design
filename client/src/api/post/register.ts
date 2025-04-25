import type { UserData } from '@/types/user'
import { apiService } from '../axios'
import { getCsrfToken } from '../get/crsf-token'

export async function register(data: UserData) {
    await getCsrfToken()
    return await apiService.post('/register', data)
}
