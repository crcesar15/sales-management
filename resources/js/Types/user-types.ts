import type { PurchaseOrder } from "./purchase-order-types";
import type { RoleResponse } from "./role-types";

interface User {
  // columns
  first_name: string;
  last_name: string;
  email: string;
  username: string;
  phone: string | null;
  status: string;
  date_of_birth: string | null;
  additional_properties?: Record<string, unknown> | null;
  password?: string;
  password_confirmation?: string;
}

interface UserPayload extends User {
  roles: number[];
}

interface UserAuth extends User {
  id: number;
  permissions: [];
}

interface UserResponse extends User {
  id: number;
  full_name: string;
  deleted_at: string;
  created_at: string;
  updated_at: string;
  email_verified_at?: string | null;
  remember_token?: string | null;

  // counts
  purchase_orders_count?: number;
  tokens_count?: number;
  roles_count?: number;
  permissions_count?: number;
  notifications_count?: number;
  // exists
  purchase_orders_exists?: boolean;
  tokens_exists?: boolean;
  roles_exists?: boolean;
  permissions_exists?: boolean;
  notifications_exists?: boolean;

  roles: RoleResponse[];
  purchase_orders?: PurchaseOrder[];
}

export type { User, UserPayload, UserResponse, UserAuth };
