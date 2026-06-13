import { useMemo } from 'react'
import { useEntitySearch, type Entity } from '@/entities/entity'
import { useEntityTypeList, type EntityType } from '@/entities/entity-type'

export interface PublicSearchTypeGroup {
  entityType: EntityType
  entities: Entity[]
}

/**
 * Site-wide search page state. Searches published entities of every type for `q`
 * and groups the hits by entity type (so each group can resolve its own labels
 * and permalink pattern). Unknown types are dropped.
 */
export function usePublicSearchPage(q: string) {
  const trimmed = q.trim()
  const searchQuery = useEntitySearch(trimmed)
  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })

  const groups = useMemo((): PublicSearchTypeGroup[] => {
    const entities = searchQuery.data?.items ?? []
    const types = entityTypeQuery.data?.items ?? []
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
  }, [searchQuery.data?.items, entityTypeQuery.data?.items])

  const total = searchQuery.data?.total ?? 0
  const hasQuery = trimmed !== ''
  const isLoading = hasQuery && (searchQuery.isLoading || entityTypeQuery.isLoading)
  const isError = searchQuery.isError || entityTypeQuery.isError

  return {
    query: trimmed,
    hasQuery,
    groups,
    total,
    isLoading,
    isError,
    errorTitle: searchQuery.error?.title ?? entityTypeQuery.error?.title ?? null,
    refetch: async () => {
      await Promise.all([searchQuery.refetch(), entityTypeQuery.refetch()])
    },
  }
}
