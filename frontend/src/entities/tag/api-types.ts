export interface TagDto {
  id: number
  name: string
  slug: string
}

export interface TagListDto {
  items: TagDto[]
  limit: number
  offset: number
}

export interface CreateTagDto {
  name: string
  slug: string
}

export type UpdateTagDto = CreateTagDto
