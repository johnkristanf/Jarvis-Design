import type { AuthenticatedUserData, LoginCredentials } from '@/types/user'
import { apiService } from '../axios'
import { getCsrfToken } from '../get/crsf-token'

export async function login(data: LoginCredentials): Promise<AuthenticatedUserData> {
    await getCsrfToken()

    const resp = await apiService.post<AuthenticatedUserData>('/login', data)
    return resp
}
