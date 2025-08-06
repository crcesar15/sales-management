import { User } from "./user-types"
import { Vendor } from "./vendor-types"
import { Product } from "./product-types"

export interface PurchaseOrder {
  // columns
  id: number
  user_id: number
  vendor_id: number
  status: string
  order_date: string | null
  expected_arrival_date: string | null
  sub_total: number | null
  discount: number | null
  total: number | null
  notes: string | null
  proof_of_payment_type: string | null
  proof_of_payment_number: string | null
  created_at: string | null
  updated_at: string | null
  // relations
  user: User
  vendor: Vendor
  products: Product[]
  // counts
  products_count: number
  // exists
  user_exists: boolean
  vendor_exists: boolean
  products_exists: boolean
}
