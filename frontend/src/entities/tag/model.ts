import type { TagId } from './ids'

export interface Tag {
  id: TagId
  name: string
  slug: string
}

export interface TagList {
  items: Tag[]
  limit: number
  offset: number
}

export interface CreateTagInput {
  name: string
  slug: string
}

export type UpdateTagInput = CreateTagInput
