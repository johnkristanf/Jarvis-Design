import type { Conversation, Customers } from '@/types/message'
import { apiService } from '../axios'

export async function getConversation(userID: number): Promise<Conversation> {
    const response = await apiService.get<Conversation>(`/api/get/convo/${userID}`)
    return response
}

export async function getAllConversation(): Promise<Conversation[]> {
    const response = await apiService.get<Conversation[]>(`/api/get/all/convo`)
    return response
}


export async function getAllCustomers(): Promise<Customers[]> {
    const response = await apiService.get<Customers[]>(`/api/get/all/customers`);
    console.log("response customers: ", response);
    return response
}