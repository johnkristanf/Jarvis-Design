import { apiService } from '../axios'
import { getCsrfToken } from '../get/crsf-token'

export async function forgotPassword(email: string): Promise<{ message: string }> {
    await getCsrfToken()

    const resp = await apiService.post<{ message: string }>('/forgot-password', { email })
    return resp
}
