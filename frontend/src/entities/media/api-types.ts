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

export interface MediaUsageDto {
  entity_id: number
  entity_type_slug: string
  entity_slug: string
  status: string
  field_key: string
  title: string | null
}

export interface MediaUsageListDto {
  items: MediaUsageDto[]
}
