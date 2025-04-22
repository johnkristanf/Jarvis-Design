import type { UpdateStatusType } from "@/types/order";
import { apiService } from "../axios";

export async function updateOrderStatus(data: UpdateStatusType): Promise<any> {
   return await apiService.put('/api/update/order/status', data);
}