import type { AuthenticatedUserData } from "@/types/user";
import { apiService } from "../axios";

export async function fetchUserData(): Promise<AuthenticatedUserData> {
    return await apiService.get('/api/user/data')
}
