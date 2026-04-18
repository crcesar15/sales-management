import type { Category } from "./category-types";
import type { Brand } from "./brand-types";
import type { MeasurementUnit } from "./measurement-unit-types";

interface ProductBase {
  brand_id?: number | null;
  measurement_unit_id?: number | null;
  name: string;
  description?: string | null;
  status: string;
}

export type Product = ProductBase;

export interface ProductOptionValue {
  id: number;
  value: string;
}

export interface ProductOption {
  id: number;
  name: string;
  values: ProductOptionValue[];
}

export interface VariantOptionValue {
  id: number;
  value: string;
  option_name: string;
}

export interface ProductResponse extends ProductBase {
  id: number;
  brand: Brand | null;
  measurement_unit: MeasurementUnit | null;
  categories: Category[];
  media: ProductMedia[];
  variants: ProductVariantInline[];
  options: ProductOption[];
  has_variants: boolean;
  categories_count?: number;
  variants_count?: number;
}

export interface ProductVariantInline {
  id: number;
  name: string;
  status: string;
  price: number;
  stock: number;
  barcode: string | null;
  identifier: string | null;
  values?: VariantOptionValue[];
  images?: ProductMedia[];
}

export interface ProductMedia {
  id: number;
  thumb_url: string;
  full_url: string;
}

export interface ProductPayload {
  name: string;
  description?: string | null;
  status: string;
  brand_id?: number | null;
  measurement_unit_id?: number | null;
  categories_ids?: number[];
  price?: number | null;
  stock?: number | null;
  barcode?: string | null;
  pending_media_ids?: number[];
  remove_media_ids?: number[];
  has_variants?: boolean;
  options?: ProductOptionCreatePayload[];
}

export interface ProductOptionCreatePayload {
  name: string;
  values: string[];
}

export interface PendingMediaResponse {
  id: number;
  thumb_url: string;
  full_url: string;
}

export interface ProductListResponse {
  id: number;
  name: string;
  description: string | null;
  status: string;
  brand: { id: number; name: string } | null;
  categories: { id: number; name: string }[];
  media: { id: number; thumb_url: string; full_url: string }[];
  variants: {
    id: number;
    identifier: string | null;
    name: string;
    option_values: { option_name: string | null; value: string }[];
    status: string;
    price: number;
    stock: number;
  }[];
  variants_count: number;
  stock: number;
  price_min: number | null;
  price_max: number | null;
  deleted_at: string | null;
  created_at: string;
}

export interface ProductFilters {
  filter?: string | null;
  status?: string;
  order_by?: string;
  order_direction?: string;
  per_page?: number;
}

export interface CreateVariant {
  key: string;
  option_values: Record<string, string>;
  price: number;
  stock: number;
  barcode: string | null;
  pending_media_ids: number[];
}
