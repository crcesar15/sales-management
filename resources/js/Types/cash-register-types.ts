// Cash Register Types — Admin management (separate from POS session types in pos.ts)

export type CashRegisterStatus = "active" | "inactive";
export type CashRegisterShiftStatus = "open" | "closed" | "forced_close";
export type CashMovementType = "cash_in" | "cash_out";

// ── Cash Register ──

interface CashRegister {
  store_id: number;
  name: string;
  code: string;
  status: CashRegisterStatus;
  is_default: boolean;
}

interface CashRegisterResponse extends CashRegister {
  id: number;
  store: { id: number; name: string; code: string };
  current_shift: CashRegisterShiftResponse | null;
  created_at: string | null;
  updated_at: string | null;
}

interface CashRegisterListResponse {
  data: CashRegisterResponse[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

interface CashRegisterFilters {
  filter?: string | null;
  status?: string;
  store_id?: number | null;
  order_by?: string;
  order_direction?: string;
  per_page?: number;
  page?: number;
}

// ── Cash Register Shift ──

interface CashRegisterShift {
  cash_register_id: number;
  user_id: number;
  status: CashRegisterShiftStatus;
  opening_balance: number;
  closing_balance: number | null;
  expected_closing: number | null;
  difference: number | null;
  opened_at: string | null;
  closed_at: string | null;
  notes: string | null;
}

interface CashRegisterShiftResponse extends CashRegisterShift {
  id: number;
  cash_register: { id: number; name: string; code: string };
  user: { id: number; full_name: string };
  movements: CashRegisterMovementResponse[];
  created_at: string | null;
  updated_at: string | null;
}

interface ShiftListResponse {
  data: CashRegisterShiftResponse[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

interface ShiftFilters {
  cash_register_id?: number | null;
  user_id?: number | null;
  status?: string;
  date_from?: string | null;
  date_to?: string | null;
  per_page?: number;
  page?: number;
}

// ── Cash Register Movement ──

interface CashRegisterMovement {
  cash_register_shift_id: number;
  user_id: number;
  type: CashMovementType;
  amount: number;
  reason: string;
}

interface CashRegisterMovementResponse extends CashRegisterMovement {
  id: number;
  user: { id: number; full_name: string };
  created_at: string | null;
  updated_at: string | null;
}

// ── Payloads ──

interface OpenShiftPayload {
  cash_register_id: number;
  opening_balance: number;
  notes?: string | null;
}

interface CloseShiftPayload {
  closing_balance: number;
  notes?: string | null;
}

interface MovementPayload {
  type: CashMovementType;
  amount: number;
  reason: string;
}

export {
  type CashRegister,
  type CashRegisterResponse,
  type CashRegisterListResponse,
  type CashRegisterFilters,
  type CashRegisterShift,
  type CashRegisterShiftResponse,
  type ShiftListResponse,
  type ShiftFilters,
  type CashRegisterMovement,
  type CashRegisterMovementResponse,
  type OpenShiftPayload,
  type CloseShiftPayload,
  type MovementPayload,
};
