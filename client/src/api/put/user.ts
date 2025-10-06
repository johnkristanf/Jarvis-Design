import { apiService } from '../axios'

export async function deductPromptLimit() {
    try {
        await apiService.patch('/api/deduct/prompt/limit', {})
    } catch (error) {
        console.error('Error deducting prompt limit: ', error)
    }
}
