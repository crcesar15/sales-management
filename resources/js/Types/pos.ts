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
  created_at: string;
  updated_at: string;
}

export interface CashRegisterShift {
  id: number;
  shift_number: string;
  register_id: number;
  cashier_id: number;
  opening_balance: number;
  closing_balance: number | null;
  expected_closing_balance: number | null;
  status: "open" | "closed";
  opened_at: string;
  closed_at: string | null;
  register?: CashRegister;
  cashier?: {
    id: number;
    name: string;
    email: string;
  };
}

export interface PosSession {
  store: {
    id: number;
    name: string;
  };
  register: CashRegister | null;
  shift: CashRegisterShift | null;
  user: {
    id: number;
    name: string;
    email: string;
  };
}

export interface PosFilters {
  store_id?: number;
  register_id?: number;
}
