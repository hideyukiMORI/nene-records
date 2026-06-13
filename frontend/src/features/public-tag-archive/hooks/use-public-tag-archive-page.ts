import { useMemo } from 'react'
import { useEntitiesByTag } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { useTagList } from '@/entities/tag'
import { groupEntitiesByType, type PublicEntityTypeGroup } from '@/features/public-entity-results'

/**
 * Tag archive page state. Lists published entities of every type carrying
 * `tagSlug`, grouped by entity type, and resolves the tag's display name.
 */
export function usePublicTagArchivePage(tagSlug: string) {
  const entitiesQuery = useEntitiesByTag(tagSlug)
  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })
  const tagQuery = useTagList({ limit: 100, offset: 0 })

  const groups = useMemo(
    (): PublicEntityTypeGroup[] =>
      groupEntitiesByType(entitiesQuery.data?.items ?? [], entityTypeQuery.data?.items ?? []),
    [entitiesQuery.data?.items, entityTypeQuery.data?.items],
  )

  const tagName = useMemo(
    () => tagQuery.data?.items.find((tag) => tag.slug === tagSlug)?.name ?? tagSlug,
    [tagQuery.data?.items, tagSlug],
  )

  const total = entitiesQuery.data?.total ?? 0
  const isLoading = entitiesQuery.isLoading || entityTypeQuery.isLoading || tagQuery.isLoading
  const isError = entitiesQuery.isError || entityTypeQuery.isError

  return {
    tagName,
    groups,
    total,
    isLoading,
    isError,
    errorTitle: entitiesQuery.error?.title ?? entityTypeQuery.error?.title ?? null,
    refetch: async () => {
      await Promise.all([entitiesQuery.refetch(), entityTypeQuery.refetch(), tagQuery.refetch()])
    },
  }
}
