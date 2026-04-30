import type { ProductVariantResponse } from "./product-variant-types";
import type { PurchaseOrder } from "./purchase-order-types";

export interface AdditionalContact {
  name: string;
  phone: string;
  email: string;
  role: string;
}

export interface Vendor {
  id: number;
  fullname: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  details: string | null;
  status: "active" | "inactive" | "archived";
  additional_contacts: AdditionalContact[] | null;
  meta: Record<string, unknown> | null;
  created_at: string | null;
  updated_at: string | null;
  // relations
  variants?: ProductVariantResponse[];
  purchase_orders?: PurchaseOrder[];
  // counts
  variants_count?: number;
  purchase_orders_count?: number;
  // exists
  variants_exists?: boolean;
  purchase_orders_exists?: boolean;
}

export interface VendorResponse extends Vendor {
  variants_count: number;
  purchase_orders_count: number;
}

export interface VendorPayload {
  fullname: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  details?: string | null;
  status: string;
  additional_contacts?: AdditionalContact[] | null;
  meta?: Record<string, unknown> | null;
}
