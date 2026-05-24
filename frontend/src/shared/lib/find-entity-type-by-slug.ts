import type { EntityType } from '@/entities/entity-type'

export function findEntityTypeBySlug(
  entityTypes: EntityType[],
  slug: string,
): EntityType | undefined {
  return entityTypes.find((item) => item.slug === slug)
}
