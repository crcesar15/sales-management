import { Category } from "./category-types"
import { Brand } from "./brand-types"
import { ProductVariant } from "./product-variant-types"
import { MeasurementUnit } from "./measurement-unit-types"

export interface Product {
  // columns
  id: number
  brand_id: number | null
  measurement_unit_id: number | null
  name: string
  description: string | null
  options: Record<string, unknown> | null
  status: string
  deleted_at: string | null
  created_at: string | null
  updated_at: string | null
  // relations
  categories: Category[]
  brand: Brand
  measurement_unit: MeasurementUnit
  variants: ProductVariant[]
  // counts
  categories_count: number
  variants_count: number
  // exists
  categories_exists: boolean
  brand_exists: boolean
  measurement_unit_exists: boolean
  variants_exists: boolean
}
