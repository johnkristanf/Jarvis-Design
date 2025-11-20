import type { Conversation, Customers } from '@/types/message'
import { apiService } from '../axios'

export async function getConversation(userID: number): Promise<Conversation> {
    const response = await apiService.get<Conversation>(`/api/get/convo/${userID}`)
    console.log("getConversation sa ORDERS: ", response);

    return response
}

export async function getAllConversation(): Promise<Conversation[]> {
    const response = await apiService.get<Conversation[]>(`/api/get/all/convo`)
    return response
}

export async function getAllCustomers(): Promise<Customers[]> {
    const response = await apiService.get<Customers[]>(`/api/get/all/customers`)
    return response
}

export async function markConversationAsRead(userId: number): Promise<any> {
    const response = await apiService.put(`/api/convo/read/${userId}`)
    return response
}
