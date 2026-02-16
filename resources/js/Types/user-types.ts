import { PurchaseOrder } from "./purchase-order-types"
import { RolePayload, RoleResponse } from './role-types'

interface User {
  // columns
  first_name: string
  last_name: string
  email: string
  username: string
  phone: string | null
  status: string
  date_of_birth: Date | null
  additional_properties?: Record<string, unknown> | null
}

interface UserPayload extends User {
  roles: RolePayload[]
}

interface UserResponse extends User {
  id: number
  full_name: string
  deleted_at: string
  created_at: string
  updated_at: string
  email_verified_at?: string | null
  password?: string
  password_confirmation?: string
  remember_token?: string | null

  // counts
  purchase_orders_count?: number
  tokens_count?: number
  roles_count?: number
  permissions_count?: number
  notifications_count?: number
  // exists
  purchase_orders_exists?: boolean
  tokens_exists?: boolean
  roles_exists?: boolean
  permissions_exists?: boolean
  notifications_exists?: boolean

  roles: RoleResponse[]
  purchase_orders?: PurchaseOrder[]
}

export { User, UserPayload, UserResponse }