export interface EntityTagDto {
  id: number
  slug: string
  name: string
}

export interface EntityTagListDto {
  items: EntityTagDto[]
}

export interface AttachEntityTagDto {
  tag_id: number
}
