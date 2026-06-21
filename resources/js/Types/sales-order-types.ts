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
    sku?: string;
    product?: {
      id: number;
      name: string;
    };
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
  variant_label: string;
  sale_unit_id: number | null;
  quantity: number;
  unit_price: number;
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
  name: string;
  identifier: string;
  label: string;
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