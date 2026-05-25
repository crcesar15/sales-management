import type { PurchaseOrderLineItem, PurchaseOrderStatus } from "./purchase-order-types";
import type { UserResponse } from "./user-types";
import type { VendorResponse } from "./vendor-types";
import type { StoreResponse } from "./store-types";
import type { ProductVariantResponse, PurchaseUnitResponse } from "./product-variant-types";

export type ReceptionOrderStatus = "pending" | "completed" | "cancelled";

export interface ReceptionOrderLineItem {
  id: number;
  reception_order_id: number;
  purchase_order_item_id: number;
  product_variant_id: number;
  quantity: number;
  price: number;
  total: number;
  expiry_date: string | null;
  batch_identifier: string | null;
  product_variant: Pick<ProductVariantResponse, "id" | "name" | "identifier" | "stock" | "minimum_stock_level" | "has_expiration"> & {
    product: Pick<ProductVariantResponse["product"], "id" | "name"> & {
      measurement_unit?: { id: number; name: string; abbreviation: string } | null;
    };
  };
  catalog_entry?: {
    id: number;
    price: number;
    unit_id: number | null;
    unit?: PurchaseUnitResponse | null;
    payment_terms: string | null;
    minimum_order_quantity: number | null;
    lead_time_days: number | null;
    details: string | null;
  } | null;
}

export interface ReceptionOrder {
  id: number;
  purchase_order_id: number;
  user_id: number;
  vendor_id: number;
  store_id: number;
  reception_date: string | null;
  notes: string | null;
  status: ReceptionOrderStatus;
  created_at: string | null;
  updated_at: string | null;
}

export interface ReceptionOrderResponse extends ReceptionOrder {
  purchase_order: {
    id: number;
    status: PurchaseOrderStatus;
    order_date: string | null;
    total: number | null;
    vendor: Pick<VendorResponse, "id" | "fullname">;
    line_items?: PurchaseOrderLineItem[];
  };
  vendor: Pick<VendorResponse, "id" | "fullname" | "email" | "phone" | "address" | "details" | "additional_contacts">;
  store: Pick<StoreResponse, "id" | "name" | "code">;
  user: Pick<UserResponse, "id" | "full_name">;
  line_items: ReceptionOrderLineItem[];
}

export interface ReceptionOrderListResponse {
  data: ReceptionOrderResponse[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface ReceptionOrderFilters {
  status: string;
  purchase_order_id: number | null;
  vendor_id: number | null;
  store_id: number | null;
  from: string;
  to: string;
  filter?: string;
  order_by?: string;
  order_direction?: string;
  per_page?: number;
}

export interface ReceptionOrderPayload {
  purchase_order_id: number;
  store_id: number;
  reception_date?: string | null;
  notes?: string | null;
  items: Array<{
    product_variant_id: number;
    purchase_order_item_id: number;
    quantity: number;
    expiry_date?: string | null;
    batch_identifier?: string | null;
  }>;
}