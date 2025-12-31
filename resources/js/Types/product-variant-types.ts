import { ProductResponse } from "./product-types"
import { Vendor } from "./vendor-types"
import { Media } from "./media-types"

interface ProductVariantBase {
  identifier?: string | null
  name: string
  price?: number
}

export interface ProductVariantResponse extends ProductVariantBase {
  // columns
  id?: number
  product_id: number
  stock?: number
  status: string,
  created_at?: string | null
  updated_at?: string | null
  // relations
  product: ProductResponse
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

export interface ProductVariantPayload extends ProductVariantBase {
  status: string,
  vendors_ids?: number[]
  media?: {id: number}[]
}
