export type MaterialFormValues = {
    material_name: string
    unit: string
    quantity: number
    reorder_level: number
    category: number
}

export type MaterialsCategory = {
    id: number
    name: string
}

export interface Material {
    id: number
    name: string
    unit: string
    quantity: number
    reorder_level: number
    category_id: number
    created_at: string
    updated_at: string
    category: MaterialsCategory
}


export type GroupedMaterials = {
    id: number,
    category_id: number,
    name: string,
    category: string

}