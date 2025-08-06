export interface Catalog {
  // columns
  id: number
  vendor_id: number
  product_variant_id: number
  price: number
  payment_terms: string | null
  details: string | null
  status: string
  created_at: string | null
  updated_at: string | null
}
