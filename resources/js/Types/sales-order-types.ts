import type { UserResponse } from "./user-types";
import type { StoreResponse } from "./store-types";
import type { PaymentMethod } from "./purchase-order-types";

export type SalesOrderStatus = "draft" | "validated" | "fulfilled" | "completed" | "cancelled";
export type SalesOrderPaymentStatus = "pending" | "partially_paid" | "paid";
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
    sku?: string;
    option_values?: string | null;
    minimum_stock_level?: number | null;
    product?: {
      id: number;
      name: string;
      brand: { id: number; name: string } | null;
      measurement_unit?: { id: number; name: string } | null;
    };
  };
  sale_unit?: {
    id: number;
    name: string;
    conversion_factor: number;
  } | null;
  stock?: number;
  stock_allocations?: SalesOrderStockAllocation[];
}

export interface SalesOrderStockAllocation {
  batch_id: number;
  quantity: number;
  batch?: {
    id: number;
    identifier: string;
    expiry_date: string | null;
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
  payment_status: SalesOrderPaymentStatus;
  discount_type: DiscountType;
  discount_value: number;
  sub_total: number;
  discount: number;
  tax_amount: number;
  total: number;
  outstanding_balance: number;
  items_count: number;
  token: string | null;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
  validated_at: string | null;
  fulfilled_at: string | null;
  completed_at: string | null;
  paid_at: string | null;
  cancelled_at: string | null;
  cancellation_reason: string | null;
  fulfilled_by: number | null;
  customer?: {
    id: number | null;
    display_name: string | null;
    email: string | null;
    first_name: string | null;
    last_name: string | null;
    phone: string | null;
    tax_id: string | null;
    tax_id_name: string | null;
  } | null;
  user?: Pick<UserResponse, "id" | "full_name">;
  fulfiller?: Pick<UserResponse, "id" | "full_name">;
  store?: Pick<StoreResponse, "id" | "name" | "code">;
  cash_register_shift?: {
    id: number;
    status: string | null;
    opened_at: string | null;
    cash_register?: {
      id: number;
      name: string;
      code: string;
    } | null;
  } | null;
  items?: SalesOrderItem[];
  payments?: SalesOrderPayment[];
}

// NOTE: payment_method is NOT on the SalesOrder header — derive from payments if needed for display

export type SalesOrderResponse = SalesOrder;

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
  search?: string;
  status?: string;
  from?: string;
  to?: string;
}

// Payload interfaces for create/update operations

export interface SalesOrderItemPayload {
  product_variant_id: number;
  sale_unit_id?: number | null;
  quantity: number;
  unit_price: number;
  conversion_factor?: number;
}

export interface SalesOrderPaymentPayload {
  payment_method: PaymentMethod;
  amount: number;
  reference?: string | null;
}

export interface SalesOrderPayload {
  customer_id?: number | null;
  discount_type: DiscountType;
  discount_value: number;
  notes?: string | null;
  items: SalesOrderItemPayload[];
  payments: SalesOrderPaymentPayload[];
}

// Line item type for create/edit form (local state, not persisted)
export interface SalesOrderLineItemForm {
  id: string;
  product_variant_id: number;
  product_name: string;
  brand_name: string | null;
  base_unit_name?: string | null;
  variant_identity?: string | null;
  variant_label: string | null;
  sale_unit_id: number | null;
  quantity: number;
  unit_price: number;
  original_unit_price: number;
  conversion_factor: number;
  line_total: number;
  stock?: number | null;
  minimum_stock_level?: number | null;
  sale_units?: Array<{
    id: number;
    name: string;
    conversion_factor: number;
    price: number;
  }>;
  sale_unit?: {
    id: number;
    name: string;
    conversion_factor: number;
  } | null;
}

// Payment form type for create/edit (local state)
export interface SalesOrderPaymentForm {
  id?: number;
  payment_method: PaymentMethod;
  amount: number;
  reference: string | null;
}

// Variant search result from the enriched search API
export interface VariantSearchResult {
  id: number;
  identifier: string | null;
  option_values: string | null;
  price: number;
  stock: number | null;
  minimum_stock_level: number | null;
  product?: {
    id: number;
    name: string;
    brand?: { id: number; name: string } | null;
    measurement_unit?: { id: number; name: string } | null;
  } | null;
  sale_units?: Array<{
    id: number;
    name: string;
    conversion_factor: number;
    price: number;
  }>;
}

// Customer option for select/autocomplete
export interface CustomerOption {
  id: number;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string | null;
  tax_id: string;
  tax_id_name: string;
}

// Store option for the sales order store selector
export interface StoreOption {
  id: number;
  name: string;
  code: string | null;
}
