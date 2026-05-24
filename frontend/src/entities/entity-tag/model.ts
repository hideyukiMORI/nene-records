import type { TagId } from '@/entities/tag'

export interface EntityTag {
  id: TagId
  slug: string
  name: string
}

export interface EntityTagList {
  items: EntityTag[]
}

export interface AttachEntityTagInput {
  entityId: number
  tagId: TagId
}

export interface DetachEntityTagInput {
  entityId: number
  tagId: TagId
}
