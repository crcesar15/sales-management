import type { UserResponse } from "./user-types";
import type { VendorResponse } from "./vendor-types";
import type { ProductVariantResponse } from "./product-variant-types";

export type PurchaseOrderStatus =
  | "draft"
  | "awaiting_approval"
  | "approved"
  | "sent"
  | "paid"
  | "cancelled";

export interface CatalogEntry {
  id: number;
  product_variant_id: number;
  vendor_id: number;
  price: number;
  payment_terms: string | null;
  details: string | null;
  unit_id: number | null;
  minimum_order_quantity: number | null;
  lead_time_days: number | null;
  unit?: { id: number; name: string; conversion_factor: number } | null;
}

export interface PurchaseOrderLineItem {
  id: number;
  purchase_order_id: number;
  product_variant_id: number;
  quantity: number;
  price: number;
  total: number;
  product_variant: Pick<ProductVariantResponse, "id" | "name" | "identifier" | "stock" | "minimum_stock_level"> & {
    product: Pick<ProductVariantResponse["product"], "id" | "name"> & {
      measurement_unit?: { id: number; name: string; abbreviation: string } | null;
    };
  };
  catalog_entry?: CatalogEntry | null;
}

export interface PurchaseOrder {
  id: number;
  user_id: number;
  vendor_id: number;
  status: PurchaseOrderStatus;
  order_date: string | null;
  expected_arrival_date: string | null;
  sub_total: number | null;
  discount: number | null;
  total: number | null;
  notes: string | null;
  proof_of_payment_type: string | null;
  proof_of_payment_number: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface PurchaseOrderResponse extends PurchaseOrder {
  user: Pick<UserResponse, "id" | "full_name">;
  vendor: Pick<VendorResponse, "id" | "fullname" | "email" | "phone" | "address" | "details" | "additional_contacts">;
  line_items: PurchaseOrderLineItem[];
}

export interface PurchaseOrderListResponse {
  data: PurchaseOrderResponse[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface PurchaseOrderFilters {
  status: string;
  vendor_id: number | null;
  from: string;
  to: string;
  filter?: string;
  order_by?: string;
  order_direction?: string;
  per_page?: number;
}

export interface PurchaseOrderPayload {
  vendor_id: number;
  order_date: string;
  expected_arrival_date?: string | null;
  discount?: number | null;
  notes?: string | null;
  items: Array<{
    product_variant_id: number;
    quantity: number;
  }>;
}