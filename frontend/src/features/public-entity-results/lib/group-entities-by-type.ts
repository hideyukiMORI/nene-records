import type { Entity } from '@/entities/entity'
import type { EntityType } from '@/entities/entity-type'

export interface PublicEntityTypeGroup {
  entityType: EntityType
  entities: Entity[]
}

/**
 * Groups published entities (of mixed types) by their entity type, preserving
 * first-seen order. Entities whose type is not in `types` are dropped.
 */
export function groupEntitiesByType(
  entities: Entity[],
  types: EntityType[],
): PublicEntityTypeGroup[] {
  const typeById = new Map(types.map((type) => [Number(type.id), type]))

  const byTypeId = new Map<number, Entity[]>()
  for (const entity of entities) {
    const typeId = entity.entityTypeId
    const bucket = byTypeId.get(typeId)
    if (bucket === undefined) {
      byTypeId.set(typeId, [entity])
    } else {
      bucket.push(entity)
    }
  }

  return [...byTypeId.entries()].flatMap(([typeId, typeEntities]) => {
    const entityType = typeById.get(typeId)
    return entityType === undefined ? [] : [{ entityType, entities: typeEntities }]
  })
}
