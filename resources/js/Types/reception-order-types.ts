export interface ReceptionOrder {
  // columns
  id: number
  purchase_order_id: number
  user_id: number
  vendor_id: number
  reception_date: string | null
  notes: string | null
  status: string
  created_at: string | null
  updated_at: string | null
}
