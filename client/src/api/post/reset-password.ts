import { apiService } from '../axios'
import { getCsrfToken } from '../get/crsf-token'

export interface ResetPasswordCredentials {
    email: string
    token: string
    password: string
    password_confirmation: string
}

export async function resetPassword(data: ResetPasswordCredentials): Promise<{ message: string }> {
    await getCsrfToken()

    const resp = await apiService.post<{ message: string }>('/reset-password', data)
    return resp
}
