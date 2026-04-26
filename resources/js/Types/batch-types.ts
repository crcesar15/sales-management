export interface Batch {
  // columns
  id: number;
  product_variant_id: number;
  reception_order_id: number;
  store_id: number;
  expiry_date: string | null;
  initial_quantity: number;
  remaining_quantity: number;
  missing_quantity: number;
  sold_quantity: number;
  transferred_quantity: number;
  status: string;
  created_at: string | null;
  updated_at: string | null;
}

export type ExpiryStatus = "ok" | "expiring_soon" | "expired" | null;

export interface BatchProductVariant {
  id: number;
  label: string;
  product_name: string;
}

export interface BatchStore {
  id: number;
  name: string;
}

export interface BatchReceptionOrder {
  id: number;
  reception_date: string | null;
}

export interface BatchResponse extends Batch {
  expiry_status: ExpiryStatus;
  product_variant: BatchProductVariant;
  store: BatchStore;
  reception_order?: BatchReceptionOrder;
}

export interface BatchListResponse {
  data: BatchResponse[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface BatchFilters {
  status: string;
  store_id: number | null;
  product_variant_id: number | null;
  expiry_from: string | null;
  expiry_to: string | null;
  expiring_soon: boolean;
}
