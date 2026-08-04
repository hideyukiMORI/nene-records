import { useMemo } from 'react'
import { useEntitiesByDateRange } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { groupEntitiesByType, type PublicEntityTypeGroup } from '@/features/public-entity-results'
import { daysInMonth, formatDateKey } from '@/shared/lib/month-grid'

/**
 * Date archive page state. Lists published entities published on a given day
 * (when `day` is set) or across a whole month, grouped by entity type.
 */
export function usePublicDateArchivePage(year: number, month: number, day: number | null) {
  const valid = year > 0 && month >= 1 && month <= 12 && (day === null || (day >= 1 && day <= 31))

  const from = valid ? formatDateKey(year, month, day ?? 1) : ''
  const to = valid ? formatDateKey(year, month, day ?? daysInMonth(year, month)) : ''

  const entitiesQuery = useEntitiesByDateRange(from, to, { enabled: valid })
  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })

  const groups = useMemo(
    (): PublicEntityTypeGroup[] =>
      groupEntitiesByType(entitiesQuery.data?.items ?? [], entityTypeQuery.data?.items ?? []),
    [entitiesQuery.data?.items, entityTypeQuery.data?.items],
  )

  return {
    valid,
    isDay: day !== null,
    total: entitiesQuery.data?.total ?? 0,
    groups,
    isLoading: valid && (entitiesQuery.isLoading || entityTypeQuery.isLoading),
    isError: entitiesQuery.isError || entityTypeQuery.isError,
    errorTitle: entitiesQuery.error?.title ?? entityTypeQuery.error?.title ?? null,
    refetch: async () => {
      await Promise.all([entitiesQuery.refetch(), entityTypeQuery.refetch()])
    },
  }
}
