
export interface Designs {
    id: number
    name: string;
    price: string;
    imageSrc: string;
    colors: { name: string; }[];
    sizes: { name: string; inStock: boolean }[];
}


export type DesignGenerate = {
    prompt: string,
    style_preference: string
}