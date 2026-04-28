export type AdjustmentReason = "physical_audit" | "robbery" | "expiry" | "damage" | "correction" | "other";

export interface StockAdjustment {
  id: number;
  product_variant_id: number;
  store_id: number;
  batch_id: number | null;
  quantity_change: number;
  reason: AdjustmentReason;
  notes: string | null;
  created_at: string;
}

export interface StockAdjustmentResponse extends StockAdjustment {
  product_variant: {
    id: number;
    name: string;
    product: {
      id: number;
      name: string;
      brand: string | null;
    };
  };
  store: {
    id: number;
    name: string;
    code: string;
  };
  user: {
    id: number;
    full_name: string;
  };
  batch: {
    id: number;
    initial_quantity: number;
    remaining_quantity: number;
  } | null;
}

export interface StockAdjustmentListResponse {
  data: StockAdjustmentResponse[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface StockAdjustmentFilters {
  store_id: number | null;
  reason: string;
  date_from: string;
  date_to: string;
}
