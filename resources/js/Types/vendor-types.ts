import { ProductVariant } from "./product-variant-types"
import { PurchaseOrder } from "./purchase-order-types"

export interface Vendor {
  // columns
  id: number
  fullname: string
  email: string | null
  phone: string | null
  address: string | null
  details: string | null
  status: string
  additional_contacts: string[] | null
  meta: Record<string, unknown> | null
  created_at: string | null
  updated_at: string | null
  // relations
  variants: ProductVariant[]
  purchase_orders: PurchaseOrder[]
  // counts
  variants_count: number
  purchase_orders_count: number
  // exists
  variants_exists: boolean
  purchase_orders_exists: boolean
}
