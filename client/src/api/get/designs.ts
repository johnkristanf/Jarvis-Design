import { apiService } from "../axios";
import type { AxiosResponse } from "axios";

export async function getAllDesigns(): Promise<any> {
  const res: AxiosResponse<any> = await apiService.get('/api/get/all/designs');
  return res; 
}


export async function getAllColors(): Promise<any> {
  const res: AxiosResponse<any> = await apiService.get('/api/get/all/colors');
  return res; 
}

export async function getAllSizes(): Promise<any> {
  const res: AxiosResponse<any> = await apiService.get('/api/get/all/sizes');
  return res; 
}