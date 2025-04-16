type DesignStatus = 'pending' | 'acknowledge' | 'tagged';

export const DesignStatus = {
    PENDING: 'pending',
    ACKNOWLEDGE: 'acknowledge',
    TAGGED: 'tagged',
} as const;


export interface Designs {
    id: number
    name: string;
    price: number;
    image_path: string;
    
}

export interface Colors {
    id: number
    name: string;
}

export interface Sizes {
    id: number
    name: string;
}



export type DesignGenerate = {
    prompt: string,
    style_preference: string
}


export interface PreferredDesign {
    id: number;
    path: string; 
    price: number; 
    size_name: string;
    color_name: string;
    status: 'pending' | 'acknowledge' | 'tagged' | string; 
    user_id: number;
    created_at: string;
}


export type UpdateUploadedDesign = {
    status: string,
    price: number,
    design_id: number
}