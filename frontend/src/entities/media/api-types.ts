export interface MediaDto {
  id: number
  url: string
  original_name: string
  mime_type: string
  size: number
  width: number | null
  height: number | null
  alt_text: string | null
  created_at: string
}

export interface MediaListDto {
  items: MediaDto[]
}
