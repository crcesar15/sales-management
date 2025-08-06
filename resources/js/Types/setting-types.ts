export interface Setting {
  // columns
  id: number
  key: string
  value: string
  name: string
  group: string
  created_at: string | null
  updated_at: string | null
}
