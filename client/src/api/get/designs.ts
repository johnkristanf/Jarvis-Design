import type { UploadedDesign } from "@/types/design";
import { apiService } from "../axios";
import type { AxiosResponse } from "axios";

export async function getAllDesigns(): Promise<any> {
  const respData: AxiosResponse<any> = await apiService.get('/api/get/all/designs');
  return respData; 
}


export async function getAllColors(): Promise<any> {
  const respData: AxiosResponse<any> = await apiService.get('/api/get/all/colors');
  return respData; 
}

export async function getAllSizes(): Promise<any> {
  const respData: AxiosResponse<any> = await apiService.get('/api/get/all/sizes');
  return respData; 
}


export async function getAllUploadedDesigns(): Promise<UploadedDesign[]> {
  const respData = await apiService.get<UploadedDesign[]>('/api/uploaded/designs');
  console.log("getAllUploadedDesigns: ", respData);
  
  return respData; 
}