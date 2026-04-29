export interface LowStockAlertItem {
  id: number;
  product_id: number;
  product_name: string | null;
  brand_name: string | null;
  name: string;
  identifier: string | null;
  barcode: string | null;
  price: number;
  total_stock: number;
  minimum_stock_level: number | null;
  status: string;
  values: Array<{ id: number; option_name: string; value: string }>;
}

export interface ExpiryAlertItem {
  id: number;
  batch_number: string;
  product_variant_id: number;
  store_id: number;
  expiry_date: string;
  initial_quantity: number;
  remaining_quantity: number;
  status: string;
  expiry_status: string | null;
  product_variant: {
    id: number;
    name: string;
    identifier: string | null;
    product: {
      id: number;
      name: string;
      brand: { id: number; name: string } | null;
    };
  };
  store: {
    id: number;
    name: string;
    code: string;
  };
}

export interface AlertsSummary {
  low_stock_count: number;
  expiry_count: number;
  total: number;
}

export interface AlertsFilters {
  store_id: number | null;
}
