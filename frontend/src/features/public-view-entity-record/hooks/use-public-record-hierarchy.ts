import { useQuery } from '@tanstack/react-query'
import {
  EMPTY_PUBLIC_RECORD_HIERARCHY,
  fetchPublicRecordHierarchy,
  type PublicRecordHierarchyDto,
  publicRecordHierarchyKeys,
} from '@/shared/lib/public-record-hierarchy'

/**
 * The record's permalink-derived breadcrumb + child pages (#651 PR2). Seeded
 * from the SSR bootstrap on first paint and refetched on client-side navigation,
 * so the section hierarchy stays correct as the visitor moves between pages.
 * Resolves to an empty hierarchy while loading or on error — it is a progressive
 * enrichment, never a blocking dependency of the record body.
 */
export function usePublicRecordHierarchy(entityId: number): PublicRecordHierarchyDto {
  const query = useQuery({
    queryKey: publicRecordHierarchyKeys.detail(entityId),
    queryFn: ({ signal }) => fetchPublicRecordHierarchy(entityId, signal),
    enabled: entityId > 0,
  })

  return query.data ?? EMPTY_PUBLIC_RECORD_HIERARCHY
}
