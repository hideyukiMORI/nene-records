export interface EntityTypeDto {
  id: number
  name: string
  slug: string
}

export interface EntityTypeListDto {
  items: EntityTypeDto[]
  limit: number
  offset: number
}

export interface CreateEntityTypeDto {
  name: string
  slug: string
}

export type UpdateEntityTypeDto = CreateEntityTypeDto
