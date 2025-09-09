import type { User } from "./user"

export interface Message {
  id: number
  content: string
  attachment_url: string | null
  attachment_temp_url: string | null
  sender_id: number
  conversation_id: number
  created_at: string
  updated_at: string
}


export interface Conversation {
  id: number
  user?: User
  messages: Message[]
  created_at: string
}


export interface Customers {
  id: number
  name: string
  email: string
}



export type UpdateChat = {
  message_id: number
  content: string
}
