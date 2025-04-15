
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