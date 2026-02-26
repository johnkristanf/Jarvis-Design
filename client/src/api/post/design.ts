import { apiService } from '../axios'

export async function saveAiDesign(file: File): Promise<{ s3_key: string }> {
    const formData = new FormData()
    formData.append('ai_design_file', file)

    const resp = await apiService.post<{ s3_key: string }>('/api/save/ai/design', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })

    return resp
}
