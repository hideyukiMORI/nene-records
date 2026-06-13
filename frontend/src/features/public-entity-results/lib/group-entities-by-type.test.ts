import { describe, expect, it } from 'vitest'
import type { Entity } from '@/entities/entity'
import type { EntityType } from '@/entities/entity-type'
import { groupEntitiesByType } from './group-entities-by-type'

function entity(id: number, entityTypeId: number): Entity {
  return { id, entityTypeId } as unknown as Entity
}

function entityType(id: number, name: string): EntityType {
  return { id, name, slug: name.toLowerCase(), permalinkPattern: null } as unknown as EntityType
}

describe('groupEntitiesByType', () => {
  it('groups entities by type in first-seen order', () => {
    const types = [entityType(1, 'Posts'), entityType(2, 'Pages')]
    const entities = [entity(10, 2), entity(11, 1), entity(12, 2)]

    const groups = groupEntitiesByType(entities, types)

    expect(groups.map((g) => g.entityType.name)).toEqual(['Pages', 'Posts'])
    expect(groups[0]?.entities.map((e) => e.id)).toEqual([10, 12])
    expect(groups[1]?.entities.map((e) => e.id)).toEqual([11])
  })

  it('drops entities whose type is unknown', () => {
    const groups = groupEntitiesByType([entity(1, 99)], [entityType(1, 'Posts')])
    expect(groups).toEqual([])
  })

  it('returns an empty array for no entities', () => {
    expect(groupEntitiesByType([], [entityType(1, 'Posts')])).toEqual([])
  })
})
