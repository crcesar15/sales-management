import { Product } from "./product-types"

export interface Brand {
  // columns
  id: number
  name: string
  created_at: string | null
  updated_at: string | null
  // relations
  products: Product[]
  // counts
  products_count: number
  // exists
  products_exists: boolean
}
