import type { Product } from "./product-types";

export interface Category {
  // columns
  id: number;
  name: string;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
  // relations
  products: Product[];
  // counts
  products_count: number;
  // exists
  products_exists: boolean;
}

export interface CategoryResponse {
  id: number;
  name: string;
  products_count: number;
  products_exists: boolean;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}
