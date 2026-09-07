// Error classes
export class PosError extends Error {
  code: string;

  constructor(message: string, code: string) {
    super(message);
    this.name = "PosError";
    this.code = code;
  }
}

export class PosPermissionError extends PosError {
  constructor(message: string) {
    super(message, "PERMISSION_DENIED");
    this.name = "PosPermissionError";
  }
}

export class PosNetworkError extends PosError {
  constructor(message: string) {
    super(message, "NETWORK_ERROR");
    this.name = "PosNetworkError";
  }
}

// Data types
export interface CashRegister {
  id: number;
  name: string;
  code: string;
  store_id: number;
  is_default: boolean;
  status: "active" | "inactive";
  current_shift?: CashRegisterShift | null;
  store?: PosStore | null;
  created_at: string;
  updated_at: string;
}

export interface CashRegisterShift {
  id: number;
  cash_register_id: number;
  user_id: number | null;
  opening_balance: number;
  closing_balance: number | null;
  expected_closing: number | null;
  difference: number | null;
  status: "open" | "closed" | "forced_close";
  opened_at: string | null;
  closed_at: string | null;
  opening_notes: string | null;
  closing_notes: string | null;
  discrepancy_reason: string | null;
  cash_register?: CashRegister;
  user?: {
    id: number;
    full_name: string;
  };
}

export interface ShiftReconciliation {
  opening_balance: number;
  cash_sales: number;
  cash_sales_count: number;
  cash_in: number;
  cash_out: number;
  expected_closing: number;
}

export interface ClosedShiftReconciliation extends ShiftReconciliation {
  counted_cash: number;
  difference: number;
}

export interface PosCloseShiftPayload {
  closing_balance: number;
  closing_notes?: string | null;
  discrepancy_reason?: string | null;
}

export interface PosMovementPayload {
  type: "cash_in" | "cash_out";
  amount: number;
  reason: string;
}

export interface PosCloseShiftResponse {
  shift: CashRegisterShift;
  summary: ClosedShiftReconciliation;
}

export interface PosStore {
  id: number;
  name: string;
  code: string;
}

export interface PosSession {
  store: PosStore | null;
  register: CashRegister | null;
  shift: CashRegisterShift | null;
}

export interface PosFilters {
  store_id?: number;
  register_id?: number;
}

export type PosCartItem = SalesOrderLineItemForm;

export type PosCustomer = CustomerOption;

export type PosDiscountType = "amount" | "percentage";
import type { CustomerOption, SalesOrderLineItemForm } from "@/Types/sales-order-types";
