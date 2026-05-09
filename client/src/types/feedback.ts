export interface FeedbackData {
    subject: string
    rating: string
    message: string
}

export interface FeedbackResponse {
    id: number
    user_id: number
    subject: string
    rating: number
    message: string
    created_at: string
    updated_at: string
    user: {
        id: number
        name: string
    }
}