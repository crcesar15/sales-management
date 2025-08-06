import { Media } from "./media-types"

export interface PendingMedia {
  // columns
  id: number
  upload_token: string
  created_at: string | null
  updated_at: string | null
  // relations
  media: Media[]
  // counts
  media_count: number
  // exists
  media_exists: boolean
}
