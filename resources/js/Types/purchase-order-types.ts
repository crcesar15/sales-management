import type { UserResponse } from "./user-types";
import type { VendorResponse } from "./vendor-types";
import type { ProductVariantResponse } from "./product-variant-types";
import type { ReceptionOrder } from "./reception-order-types";

export type PurchaseOrderStatus = "draft" | "awaiting_approval" | "approved" | "sent" | "partially_received" | "received" | "cancelled";

export type PaymentMethod = "cash" | "credit_card" | "qr" | "transfer";

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
  catalog_id: number | null;
  catalog: Pick<CatalogEntry, "id" | "price" | "payment_terms" | "details" | "unit_id" | "minimum_order_quantity" | "lead_time_days"> & {
    unit?: { id: number; name: string; conversion_factor: number } | null;
  };
  unit_id: number | null;
  quantity: number;
  received_quantity: number;
  remaining_quantity: number;
  price: number;
  total: number;
  product_variant: Pick<ProductVariantResponse, "id" | "name" | "identifier" | "stock" | "minimum_stock_level" | "has_expiration"> & {
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
  completion_notes: string | null;
  is_paid: boolean;
  paid_at: string | null;
  proof_of_payment_type: PaymentMethod | null;
  proof_of_payment_number: string | null;
  is_fully_received: boolean;
  created_at: string | null;
  updated_at: string | null;
}

export interface PurchaseOrderResponse extends PurchaseOrder {
  user: Pick<UserResponse, "id" | "full_name">;
  vendor: Pick<VendorResponse, "id" | "fullname" | "email" | "phone" | "address" | "details" | "additional_contacts">;
  line_items: PurchaseOrderLineItem[];
  reception_orders?: Array<
    ReceptionOrder & {
      vendor: Pick<VendorResponse, "id" | "fullname">;
      store: { id: number; name: string; code: string };
      user: Pick<UserResponse, "id" | "full_name">;
    }
  >;
}

export interface ProofOfPaymentMedia {
  id: number;
  file_name: string;
  mime_type: string;
  size: number;
  url: string;
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
    catalog_id: number;
    unit_id?: number | null;
    quantity: number;
    price: number;
  }>;
}
