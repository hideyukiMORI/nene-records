export interface MediaDto {
  id: number
  url: string
  original_name: string
  mime_type: string
  size: number
  created_at: string
}

export interface MediaListDto {
  items: MediaDto[]
}
