export type Products = {
  id: number
  name: string
  unit_price: string
  category_id: number
  design_category: {
    id: number
    name: string
  }
}