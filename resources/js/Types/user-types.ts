import { PurchaseOrder } from "./purchase-order-types"
import { Role } from './role-types'

export interface User {
  // columns
  id: number
  first_name: string
  last_name: string
  email: string
  username: string
  phone: string | null
  status: string
  date_of_birth: string | null
  additional_properties: Record<string, unknown> | null
  email_verified_at: string | null
  password?: string
  remember_token?: string | null
  deleted_at: string | null
  created_at: string | null
  updated_at: string | null
  // mutators
  full_name: string
  // relations
  purchase_orders: PurchaseOrder[]
  roles: Role[]
  // permissions: Permission[]
  // counts
  purchase_orders_count: number
  tokens_count: number
  roles_count: number
  permissions_count: number
  notifications_count: number
  // exists
  purchase_orders_exists: boolean
  tokens_exists: boolean
  roles_exists: boolean
  permissions_exists: boolean
  notifications_exists: boolean
}
