export interface SalesOrder {
  // columns
  id: number
  customer_id: number
  user_id: number
  status: string
  payment_method: string
  notes: string | null
  sub_total: number
  discount: number
  total: number
  created_at: string | null
  updated_at: string | null
}
