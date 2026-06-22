import type { EntityType, EntityTypeId } from '@/entities/entity-type/index.ts'
import { toEntityTypeId } from '@/entities/entity-type/index.ts'

export function buildEntityTypeId(value = 1): EntityTypeId {
  return toEntityTypeId(value)
}

export function buildEntityType(overrides: Partial<EntityType> = {}): EntityType {
  return {
    id: toEntityTypeId(1),
    name: 'Article',
    slug: 'article',
    isPinned: false,
    defaultLayout: 'standard',
    displayOrder: 0,
    ...overrides,
  }
}
