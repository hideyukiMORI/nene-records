import { useMemo } from 'react'
import { defaultEntityListParams, useEntityList } from '@/entities/entity'

/**
 * Resolve an entity slug to its numeric ID within a given entity type.
 * Uses the list API with a text search (q=slug) and filters client-side
 * for an exact slug match so date-based patterns like /posts/2024/01/my-article work.
 */
export function useEntityIdBySlug(entityTypeId: number, entitySlug: string) {
  const params = useMemo(
    () => ({
      ...defaultEntityListParams(entityTypeId, [], {}, 0, entitySlug),
      status: 'published' as const,
      limit: 50,
    }),
    [entityTypeId, entitySlug],
  )

  const query = useEntityList(params, { enabled: entityTypeId > 0 && entitySlug !== '' })

  const entityId = useMemo(() => {
    const match = (query.data?.items ?? []).find((e) => e.slug === entitySlug)
    return match !== undefined ? Number(match.id) : null
  }, [query.data?.items, entitySlug])

  return {
    entityId,
    isLoading: query.isLoading,
    isError: query.isError,
  }
}
