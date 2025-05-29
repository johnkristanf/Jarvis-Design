type DesignStatus = 'pending' | 'acknowledge' | 'tagged'

export const DesignStatus = {
    PENDING: 'pending',
    ACKNOWLEDGE: 'acknowledge',
    TAGGED: 'tagged',
} as const

export interface FabricTypes {
    id: number
    name: string
}

export type DesignCategories = {
    id: number
    name: string
}

export interface Designs {
    id: number
    name: string
    design_categories: DesignCategories
    price: number
    quantity: number
    image_path: string
}

export interface Product {
    id: number
    name: string
    unit_price: string
    category_id: number
    fabric_type: FabricTypes
    created_at: string
    updated_at: string
}

export interface DesignCategory {
    id: number
    name: string
    is_fixed_priced: boolean
    fixed_price: string | null
    products: Product[]
}

export type GroupedDesignsResponse = DesignCategory[]

export type BusinessProductDesign = {
    id: number
    image_url: string
    temp_url: string
}

export interface Colors {
    id: number
    name: string
}

export interface Sizes {
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
    material_id: number
    quantity_used: number
}

export type DesignMaterialAttachmentData = {
    design_id: number
    designType: string
    material_quantity_arr: MaterialQuantityAttach[]
}

export type UploadedDesignsByID = {
    temporary_url: string
}

export const sublimationProductCategories = [
    'Basketball Apparel',
    'Volleyball Apparel',
    'T-shirts',
    'Polo Shirts',
    'Varsity Jackets',
    'Longsleeve Shirt',
]
