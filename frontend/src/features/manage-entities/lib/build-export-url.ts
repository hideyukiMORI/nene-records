export function buildExportUrl(entityTypeId: number, format: 'csv' | 'json', q?: string): string {
  const params = new URLSearchParams({
    entity_type_id: String(entityTypeId),
    format,
  })
  if (q !== undefined && q !== '') {
    params.set('q', q)
  }
  return `/api/v1/entities/export?${params.toString()}`
}
