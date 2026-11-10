export interface DashboardStore {
  id: number;
  name: string;
  code: string;
  archived: boolean;
  status: string;
}

export interface DashboardFilters {
  store_id: number | null;
  preset: "today" | "last_7_days" | "last_30_days" | "month_to_date" | "custom";
  comparison: "automatic" | "off";
  from: string;
  to: string;
}

export interface Profitability {
  complete: boolean;
  gross_profit: number | null;
  margin: number | null;
  cost_of_goods: number | null;
  known_cost: number;
  known_profit: number;
  missing_lines: number;
  cost_coverage: number;
}

export interface SalesSummary {
  net_sales: number;
  order_count: number;
  average_sale: number | null;
  profitability: Profitability | null;
}

export interface SalesTrend {
  granularity: "hour" | "day" | "week";
  points: Array<{ at: string; sales: number; comparison: number | null; comparison_at: string | null }>;
}

export interface ProductPerformance {
  variant_id: number;
  name: string;
  identifier: string | null;
  net_sales: number;
  profitability: Profitability | null;
}

export interface InventoryAttention {
  stockouts: number;
  low_stock: number;
  expired: number;
  expiring: number;
  expiry_days: number;
  stock_items: Array<{
    variant_id: number;
    name: string;
    identifier: string | null;
    store_id: number;
    store_name: string;
    quantity: number;
    minimum: number | null;
  }>;
  expiry_items: Array<{
    batch_id: number;
    variant_id: number;
    product_name: string;
    store_id: number;
    store_name: string;
    quantity: number;
    expiry_date: string;
  }>;
}

export interface DashboardCollections {
  total: number;
  methods: Array<{ method: "cash" | "credit_card" | "qr" | "transfer"; amount: number }>;
}

export interface DashboardShifts {
  open_count: number;
  closed_count: number;
  shortages: number;
  overages: number;
  open: Array<{ id: number; register_name: string; store_name: string; cashier: string; expected_cash: number; opened_at: string }>;
}

export interface StorePerformance {
  data: Array<{ id: number; name: string; code: string; net_sales: number; order_count: number; profitability: Profitability | null }>;
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface DashboardData {
  generated_at: string;
  filters: DashboardFilters;
  period: { start: string; end: string; comparison_start: string | null; comparison_end: string | null; timezone: string };
  capabilities: { sales: boolean; profit: boolean; inventory: boolean; shifts: boolean; inventory_details: boolean; all_sales: boolean };
  store_count: number;
  sales: SalesSummary | null;
  comparison: SalesSummary | null;
  trend: SalesTrend | null;
  products: ProductPerformance[] | null;
  inventory: InventoryAttention | null;
  collections: DashboardCollections | null;
  shifts: DashboardShifts | null;
  stores: StorePerformance | null;
}
