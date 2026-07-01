export interface Customer {
  id: number;
  first_name: string | null;
  last_name: string | null;
  email: string | null;
  phone: string | null;
  tax_id: string;
  tax_id_name: string;
  status: "active" | "inactive";
  created_at: string | null;
  updated_at: string | null;
  sales_orders_count?: number;
}

export interface CustomerResponse {
  id: number;
  first_name: string | null;
  last_name: string | null;
  email: string | null;
  phone: string | null;
  tax_id: string;
  tax_id_name: string;
  status: "active" | "inactive";
  sales_orders_count: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface CustomerPayload {
  first_name?: string | null;
  last_name?: string | null;
  email?: string | null;
  phone?: string | null;
  tax_id: string;
  tax_id_name: string;
  status: string;
}
