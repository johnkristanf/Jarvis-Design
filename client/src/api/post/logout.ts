import { apiService } from "../axios";

export async function logoutUser() {
    return await apiService.post('/logout')
}