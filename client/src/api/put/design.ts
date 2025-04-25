import type { UpdateUploadedDesign } from '@/types/design'
import { apiService } from '../axios'

export async function updateUploadedDesign(data: UpdateUploadedDesign): Promise<any> {
    return await apiService.put('/api/update/uploaded/design', data)
}
