import type { Product } from "./product-types"

export interface MeasurementUnit {
  // columns
  id: number
  name: string
  abbreviation: string
  created_at: string | null
  updated_at: string | null
  deleted_at: string | null
  // relations
  products?: Product[]
  // counts
  products_count?: number
  // exists
  products_exists?: boolean
}

export interface MeasurementUnitResponse {
  id: number
  name: string
  abbreviation: string
  created_at: string | null
  updated_at: string | null
  deleted_at: string | null
}
