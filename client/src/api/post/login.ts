import type { UserData } from "@/types/user";
import { apiService } from "../axios";
import { getCsrfToken } from "../get/crsf-token";

export async function login(data: UserData) {
    await getCsrfToken();
    return await apiService.post('/login', data)
}
