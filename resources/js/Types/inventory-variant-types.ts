import type { MeasurementUnit } from "./measurement-unit-types";
import type { VariantOptionValue, ProductMedia } from "./product-types";

export interface InventoryVariantListItem {
  id: number;
  product_id: number;
  product_name: string | null;
  brand_name: string | null;
  name: string;
  identifier: string | null;
  barcode: string | null;
  price: number;
  stock: number;
  status: string;
  is_default: boolean;
  values: VariantOptionValue[];
  images: ProductMedia[];
  created_at: string;
}

export interface InventoryVariantListResponse {
  data: InventoryVariantListItem[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface InventoryFilters {
  status: string;
  filter: string;
}

export interface InventoryVariantDetail {
  id: number;
  product_id: number;
  identifier: string | null;
  barcode: string | null;
  price: number;
  stock: number;
  status: string;
  name: string;
  values: VariantOptionValue[];
  images: ProductMedia[];
  sale_units: VariantUnitResource[];
  purchase_units: VariantUnitResource[];
  created_at: string;
  updated_at: string;
}

export interface InventoryProductDetail {
  id: number;
  name: string;
  description: string | null;
  status: string;
  brand: { id: number; name: string } | null;
  categories: Array<{ id: number; name: string }>;
  measurement_unit: MeasurementUnit | null;
  media: ProductMedia[];
  deleted_at: string | null;
  created_at: string | null;
}

export interface VariantUnitResource {
  id: number;
  type: "sale" | "purchase";
  name: string;
  conversion_factor: number;
  price: number | null;
  status: "active" | "inactive";
  sort_order: number;
}
