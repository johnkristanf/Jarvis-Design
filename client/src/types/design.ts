type DesignStatus = 'pending' | 'acknowledge' | 'tagged'

export const DesignStatus = {
    PENDING: 'pending',
    ACKNOWLEDGE: 'acknowledge',
    TAGGED: 'tagged',
} as const

export interface Designs {
    id: number
    name: string
    price: number
    quantity: number
    image_path: string
}

export interface Colors {
    id: number
    name: string
}

export interface Sizes {
    id: number
    name: string
}


export interface DesignCategory {
    id: number
    name: string
}

export type DesignGenerate = {
    prompt: string
    style_preference: string
}

export interface UploadedDesign {
    id: number
    order_option: string
    path: string
    
    price: number
    quantity: number

    size: Sizes
    color: Colors

    status: 'pending' | 'acknowledge' | 'tagged' | string
    user_id: number
    created_at: string
}

export type UpdateUploadedDesign = {
    status: string
    price: number
    design_id: number
}


type MaterialQuantityAttach = {
    material_id: number, 
    quantity_used: number;
}

export type DesignMaterialAttachmentData = {
    design_id: number,
    designType: string,
    material_quantity_arr: MaterialQuantityAttach[]
}


export type UploadedDesignsByID = {
    temporary_url: string
}