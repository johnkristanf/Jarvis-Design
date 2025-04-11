
export interface Product {
    id: number
    name: string;
    price: string;
    imageSrc: string;
    colors: { name: string; class: string; selectedClass: string }[];
    sizes: { name: string; inStock: boolean }[];
}
