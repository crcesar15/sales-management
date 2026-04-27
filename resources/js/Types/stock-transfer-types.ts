export type TransferStatus = "requested" | "picked" | "in_transit" | "received" | "completed" | "cancelled";

export interface StockTransferItem {
  id: number;
  product_variant_id: number;
  quantity_requested: number;
  quantity_sent: number;
  quantity_received: number;
  product_variant?: {
    id: number;
    label: string;
    product_name: string;
  };
}

export interface StockTransfer {
  id: number;
  status: TransferStatus;
  notes: string | null;
  cancelled_at: string | null;
  completed_at: string | null;
  created_at: string | null;
  from_store_id: number;
  to_store_id: number;
}

export interface StockTransferResponse extends StockTransfer {
  from_store: { id: number; name: string; code: string };
  to_store: { id: number; name: string; code: string };
  requested_by_user: { id: number; full_name: string };
  items: StockTransferItem[];
}

export interface StockTransferListResponse {
  data: StockTransferResponse[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface StockTransferFilters {
  status: string;
  from_store_id: number | null;
  to_store_id: number | null;
}
