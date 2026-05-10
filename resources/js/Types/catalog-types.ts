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
    product: ProductResponse;
    values: { option_name: string; value: string }[];
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
  catalog_entries: CatalogResponse[];
  lowest_price: number;
}

export interface CatalogFilters {
  filter?: string | null;
  status?: string;
  sort_field?: string;
  sort_direction?: string;
  per_page?: number;
  vendor_id?: number | null;
}
