import type { VariantOptionValue, ProductMedia } from "./product-types";

export interface StockOverviewItem {
  id: number;
  product_id: number;
  product_name: string | null;
  brand_name: string | null;
  name: string;
  identifier: string | null;
  barcode: string | null;
  price: number;
  status: string;
  total_stock: number;
  is_low_stock: boolean;
  values: VariantOptionValue[];
  images: ProductMedia[];
  created_at: string;
}

export interface StockOverviewResponse {
  data: StockOverviewItem[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface StockOverviewFilters {
  store_id: number | null;
  category_id: number | null;
  brand_id: number | null;
  low_stock: boolean;
  search: string;
  order_by: string;
  order_direction: string;
}

export interface StockStoreBreakdown {
  store_id: number;
  store_name: string;
  store_code: string;
  quantity: number;
}

export interface StockVariantDetail {
  variant: {
    id: number;
    product_id: number;
    product_name: string | null;
    brand_name: string | null;
    name: string;
    identifier: string | null;
    barcode: string | null;
    price: number;
    status: string;
    total_stock: number;
    is_low_stock: boolean;
    values: VariantOptionValue[];
  };
  stores: StockStoreBreakdown[];
}
