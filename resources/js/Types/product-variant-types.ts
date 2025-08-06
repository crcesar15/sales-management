import { Product } from "./product-types"
import { Vendor } from "./vendor-types"
import { Media } from "./media-types"

export interface ProductVariant {
  // columns
  id: number
  product_id: number
  identifier: string | null
  name: string
  price: number
  stock: number
  status: string
  created_at: string | null
  updated_at: string | null
  // relations
  product: Product
  vendors: Vendor[]
  media: Media[]
  // counts
  vendors_count: number
  media_count: number
  // exists
  product_exists: boolean
  vendors_exists: boolean
  media_exists: boolean
}
