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
