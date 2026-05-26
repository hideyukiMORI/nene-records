export interface EntityTypeDto {
  id: number
  name: string
  slug: string
  is_pinned: boolean
}

export interface EntityTypeListDto {
  items: EntityTypeDto[]
  limit: number
  offset: number
}

export interface CreateEntityTypeDto {
  name: string
  slug: string
  is_pinned?: boolean
}

export type UpdateEntityTypeDto = CreateEntityTypeDto
