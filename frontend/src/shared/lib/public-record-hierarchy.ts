import { apiClient } from '@/shared/api/client'
import type { components } from '@/shared/api/schema.gen'

export type PublicRecordHierarchyDto = components['schemas']['PublicRecordHierarchy']
export type PublicRecordBreadcrumbDto = components['schemas']['PublicRecordBreadcrumb']
export type PublicRecordChildLinkDto = components['schemas']['PublicRecordChildLink']

export const EMPTY_PUBLIC_RECORD_HIERARCHY: PublicRecordHierarchyDto = {
  breadcrumbs: [],
  childPages: [],
}

export const publicRecordHierarchyKeys = {
  detail: (entityId: number) => ['public-record-hierarchy', entityId] as const,
}

/**
 * Fetch a record's permalink-derived breadcrumb + child pages (#651 PR2). Seeded
 * into the cache from the SSR bootstrap on first paint; called again only on
 * client-side navigation to a different record.
 */
export function fetchPublicRecordHierarchy(
  entityId: number,
  signal?: AbortSignal,
): Promise<PublicRecordHierarchyDto> {
  return apiClient.get<PublicRecordHierarchyDto>(
    `/api/v1/public/records/${String(entityId)}/hierarchy`,
    signal,
  )
}
