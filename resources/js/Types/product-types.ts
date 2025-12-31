import { Category } from "./category-types"
import { Brand } from "./brand-types"
import { ProductVariantResponse } from "./product-variant-types"
import { ProductVariantPayload } from "./product-variant-types"
import { MeasurementUnit } from "./measurement-unit-types"

interface ProductBase {
  brand_id?: number | null
  measurement_unit_id?: number | null
  name: string
  description?: string | null
  options?: Array<{name: string; values: string[]; saved: boolean}> | null
  status: string,
  categories: Category[]
}

export interface ProductResponse extends ProductBase {
  // columns
  id: number
  // relations
  brand: Brand
  measurement_unit: MeasurementUnit
  variants: ProductVariantResponse[]
  // counts
  categories_count?: number
  variants_count?: number
  // exists
  categories_exists?: boolean
  brand_exists?: boolean
  measurement_unit_exists?: boolean
  variants_exists?: boolean
}

export interface ProductPayload extends ProductBase {
  categories_ids?: number[]
  media?: {id: number}[]
  variants?: ProductVariantPayload[]
}