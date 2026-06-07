import type { CustomerResponse } from "./customer-types";
import type { UserResponse } from "./user-types";
import type { StoreResponse } from "./store-types";
import type { PaymentMethod } from "./purchase-order-types";

export type SalesOrderStatus = "draft" | "sent" | "paid" | "held" | "cancelled";
export type DiscountType = "flat" | "percentage";

export interface SalesOrderItem {
  id: number;
  sales_order_id: number;
  product_variant_id: number;
  sale_unit_id: number | null;
  quantity: number;
  unit_price: number;
  conversion_factor: number;
  line_total: number;
  product_variant?: {
    id: number;
    name: string;
    identifier: string;
  };
  sale_unit?: {
    id: number;
    name: string;
    conversion_factor: number;
  } | null;
}

export interface SalesOrderPayment {
  id: number;
  sales_order_id: number;
  payment_method: PaymentMethod;
  amount: number;
  reference: string | null;
}

export interface SalesOrder {
  id: number;
  customer_id: number | null;
  user_id: number;
  store_id: number;
  cash_register_shift_id: number | null;
  status: SalesOrderStatus;
  discount_type: DiscountType;
  discount_value: number;
  sub_total: number;
  discount: number;
  tax_amount: number;
  total: number;
  token: string | null;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
  customer?: CustomerResponse;
  user?: Pick<UserResponse, "id" | "full_name">;
  store?: Pick<StoreResponse, "id" | "name" | "code">;
  cash_register_shift?: {
    id: number;
    status: string;
  } | null;
  items?: SalesOrderItem[];
  payments?: SalesOrderPayment[];
}

export interface SalesOrderResponse extends SalesOrder {}

export interface SalesOrderListResponse {
  data: SalesOrderResponse[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface SalesOrderFilters {
  status: string;
  store_id: number | null;
  customer_id: number | null;
  filter?: string;
  order_by?: string;
  order_direction?: string;
  per_page?: number;
}