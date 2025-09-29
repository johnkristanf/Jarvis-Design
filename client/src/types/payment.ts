export type PaymentStatus = 'in_review' | 'partially_paid' | 'fully_paid'

export interface Payment {
    id: number
    order_id: number
    payment_method_id: number
    payment_number: string
    amount_applied: number
    payment_attachments: PaymentAttachments
    payment_methods: PaymentMethods
    status: PaymentStatus
    created_at: string
    updated_at: string
}

export interface PaymentAttachments {
    id: number
    order_payment_id: number
    temp_url: string
    url: string
}

export interface PaymentMethods {
    id: number
    name: string
}

export interface UpdatePaymentPayload {
    id: number
    amount: number
}

export type ProceedPaymentData = {
    order_option: string
    price: number
    name: string
}

export type DesignAttribute = {
    design_id: number
    color: number
    size: number
    quantity: number
}

export type ProceedPaymentResponseData = {
    code_id: string
    amount: number
    business_name: string
    qrcode_img_src: string
}
