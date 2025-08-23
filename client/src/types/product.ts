export type Products = {
  id: number
  name: string
  unit_price: string
  fabric_quantity: string
  category_id: number
  design_images: string[]
  design_category: {
    id: number
    name: string
  }

}