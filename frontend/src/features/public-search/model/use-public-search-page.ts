import { useMemo } from 'react'
import { useEntitySearch } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { groupEntitiesByType, type PublicEntityTypeGroup } from '@/features/public-entity-results'

/**
 * Site-wide search page state. Searches published entities of every type for `q`
 * and groups the hits by entity type (so each group can resolve its own labels
 * and permalink pattern).
 */
export function usePublicSearchPage(q: string) {
  const trimmed = q.trim()
  const searchQuery = useEntitySearch(trimmed)
  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })

  const groups = useMemo(
    (): PublicEntityTypeGroup[] =>
      groupEntitiesByType(searchQuery.data?.items ?? [], entityTypeQuery.data?.items ?? []),
    [searchQuery.data?.items, entityTypeQuery.data?.items],
  )

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
