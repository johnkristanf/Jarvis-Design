import type { UserData } from "@/types/user";
import { apiService } from "../axios";

export async function fetchUserData(): Promise<UserData> {
    return await apiService.get('/api/user/data')
}
