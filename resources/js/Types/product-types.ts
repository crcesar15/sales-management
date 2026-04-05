import { Category } from "./category-types"
import { Brand } from "./brand-types"
import { MeasurementUnit } from "./measurement-unit-types"

import type { ProductVariant } from "./product-variant-types"

interface ProductBase {
  brand_id?: number | null
  measurement_unit_id?: number | null
  name: string
  description?: string | null
  status: string
}

export type Product = ProductBase

export interface ProductResponse extends ProductBase {
  id: number
  brand: Brand | null
  measurement_unit: MeasurementUnit | null
  categories: Category[]
  media: ProductMedia[]
  variants: ProductVariantInline[]
  categories_count?: number
  variants_count?: number
}

export interface ProductVariantInline {
  id: number
  name: string
  status: string
  price: number
  stock: number
  barcode: string | null
  identifier: string | null
}

export interface ProductMedia {
  id: number
  thumb_url: string
  full_url: string
}

export interface ProductPayload {
  name: string
  description?: string | null
  status: string
  brand_id?: number | null
  measurement_unit_id?: number | null
  categories_ids?: number[]
  price: number
  stock: number
  barcode?: string | null
  pending_media_ids?: number[]
  remove_media_ids?: number[]
}

export interface PendingMediaResponse {
  id: number
  thumb_url: string
  full_url: string
}

export interface ProductListResponse {
  id: number
  name: string
  description: string | null
  status: string
  brand: { id: number; name: string } | null
  categories: { id: number; name: string }[]
  media: { id: number; thumb_url: string; full_url: string }[]
  variants: { id: number; name: string; status: string; price: number; stock: number }[]
  variants_count: number
  stock: number
  price_min: number | null
  price_max: number | null
  deleted_at: string | null
  created_at: string
}

export interface ProductFilters {
  filter?: string | null
  status?: string
  order_by?: string
  order_direction?: string
  per_page?: number
}
