import type { User } from "./user"

export interface Message {
  id: number
  content: string
  sender_id: number
  message_attachments: MessageAttachment[]
  conversation_id: number
  created_at: string
  updated_at: string
}

interface MessageAttachment {
  id: number
  attachment_url: string
  attachment_temp_url: string
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
