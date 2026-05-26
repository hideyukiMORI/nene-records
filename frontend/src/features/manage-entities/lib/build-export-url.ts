import type { EntityStatus } from '@/entities/entity'

export function buildExportUrl(
  entityTypeId: number,
  format: 'csv' | 'json',
  q?: string,
  status?: EntityStatus,
): string {
  const params = new URLSearchParams({
    entity_type_id: String(entityTypeId),
    format,
  })
  if (q !== undefined && q !== '') {
    params.set('q', q)
  }
  if (status !== undefined) {
    params.set('status', status)
  }
  return `/api/v1/entities/export?${params.toString()}`
}
