import type { DesignGenerate } from "@/types/design";
import axios, { type AxiosResponse } from "axios";
import { apiService } from "../axios";

export async function generateImageDesign(data: DesignGenerate): Promise<AxiosResponse> {
   return axios.post(`${import.meta.env.VITE_AI_API_URL}/generate/design`, data);
}

export async function uploadPreferredDesign(formData: FormData): Promise<AxiosResponse> {
   return await apiService.post('/api/upload/preferred/design', formData);
}