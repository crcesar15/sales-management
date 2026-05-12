import type { ProductResponse } from "./product-types";

export interface Catalog {
  // columns
  id: number;
  vendor_id: number;
  product_variant_id: number;
  unit_id: number | null;
  price: number;
  payment_terms: string | null;
  details: string | null;
  status: "active" | "inactive";
  minimum_order_quantity: number | null;
  lead_time_days: number | null;
  created_at: string | null;
  updated_at: string | null;
  // relations
  vendor?: { id: number; fullname: string };
  product_variant?: {
    id: number;
    name: string;
    identifier: string;
    product: ProductResponse & {
      brand: { id: number; name: string } | null;
      measurement_unit: { id: number; name: string; abbreviation: string } | null;
    };
    values: { option_name: string; value: string }[];
    purchase_units: { id: number; name: string }[];
  };
  purchase_unit?: { id: number; name: string; conversion_factor: number };
}

export interface CatalogResponse extends Catalog {
  id: number;
}

export interface CatalogPayload {
  [key: string]: any;
  vendor_id: number;
  product_variant_id: number;
  unit_id?: number | null;
  price: number;
  payment_terms?: string | null;
  details?: string | null;
  status: "active" | "inactive";
  minimum_order_quantity?: number | null;
  lead_time_days?: number | null;
}

export interface CatalogGroupedEntry {
  product_variant_id: number;
  product_name: string;
  variant_name: string;
  brand_name: string | null;
  purchase_units: string[];
  measurement_unit: string | null;
  catalog_entries: CatalogResponse[];
}

export interface CatalogVariantVendor {
  id: number;
  vendor: { id: number; fullname: string } | null;
  price: number;
  unit: { id: number; name: string; conversion_factor: number } | null;
  payment_terms: string | null;
  minimum_order_quantity: number | null;
  lead_time_days: number | null;
  status: "active" | "inactive";
}

export interface CatalogVariantResponse {
  id: number;
  identifier: string | null;
  barcode: string | null;
  price: number;
  stock: number;
  status: "active" | "inactive" | "archived";
  name: string;
  product: {
    id: number;
    name: string;
    brand: { id: number; name: string } | null;
    measurement_unit: { id: number; name: string; abbreviation: string } | null;
  } | null;
  values: { option_name: string; value: string }[];
  purchase_units: { id: number; name: string }[];
  vendor_count: number;
  vendors: CatalogVariantVendor[];
}

export interface CatalogVariantCollection {
  data: CatalogVariantResponse[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface CatalogShowProductVariant {
  id: number;
  product_id: number;
  identifier: string | null;
  barcode: string | null;
  price: number;
  stock: number;
  status: string;
  name: string;
  product: {
    id: number;
    name: string;
    brand: { id: number; name: string } | null;
    measurement_unit: { id: number; name: string; abbreviation: string } | null;
    categories: { id: number; name: string }[] | null;
  } | null;
  values: { id: number; value: string; option_name: string | null }[];
  created_at: string | null;
  updated_at: string | null;
}

export interface CatalogShowProps {
  productVariant: CatalogShowProductVariant;
  catalogEntries: CatalogResponse[];
}

export interface CatalogFilters {
  filter?: string | null;
  status?: string;
  sort_field?: string;
  sort_direction?: string;
  per_page?: number;
  vendor_id?: number | null;
}
