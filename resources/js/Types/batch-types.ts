export interface Batch {
  // columns
  id: number
  product_variant_id: number
  reception_order_id: number
  expiry_date: string | null
  initial_quantity: number
  remaining_quantity: number
  missing_quantity: number
  sold_quantity: number
  transferred_quantity: number
  status: string
  created_at: string | null
  updated_at: string | null
}
