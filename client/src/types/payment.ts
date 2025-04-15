export type ProceedPaymentData = {
    amount: number,
    name: string
}

export type PreferredDesignAttribute = {
    color: number,
    size:  number
}


export type ProceedPaymentResponseData = {
    code_id: string,
    amount: number,
    business_name: string,
    qrcode_img_src: string,
}