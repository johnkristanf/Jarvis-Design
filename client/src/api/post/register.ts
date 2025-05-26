import type { RegistrationCredentials } from '@/types/user'
import { apiService } from '../axios'
import { getCsrfToken } from '../get/crsf-token'

export async function register(data: RegistrationCredentials) {
    await getCsrfToken()
    return await apiService.post('/register', data)
}
