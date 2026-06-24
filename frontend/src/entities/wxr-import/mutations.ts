import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'

/** A single item the import would create (preview). */
export interface WxrPlannedItem {
  title: string
  slug: string
  entity_type: string
  status: string
  tags: string[]
}

export interface WxrSkippedItem {
  title: string
  reason: string
}

/** Dry-run preview returned by POST /api/v1/migration/wxr (dry_run=true). */
export interface WxrImportPlanDto {
  mode: 'preview'
  planned_count: number
  skipped_count: number
  counts_by_entity_type: Record<string, number>
  counts_by_status: Record<string, number>
  tags: string[]
  warnings: string[]
  planned: WxrPlannedItem[]
  skipped: WxrSkippedItem[]
}

/** Result returned when executed (dry_run=false). */
export interface WxrImportResultDto {
  mode: 'import'
  created_entities: number
  skipped_existing: number
  tags_ensured: number
  tag_links: number
  redirects_created: number
  media_imported: number
  media_skipped: number
  skipped: WxrSkippedItem[]
  warnings: string[]
}

const ENDPOINT = '/api/v1/migration/wxr'

function uploadWxr<T>(file: File, dryRun: boolean): Promise<T> {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('dry_run', dryRun ? 'true' : 'false')
  return apiClient.upload<T>(ENDPOINT, formData)
}

/** Preview (dry-run) a WXR import without writing. */
export function useWxrPreview(): UseMutationResult<WxrImportPlanDto, AppError, File> {
  return useMutation({
    mutationFn: (file: File) => uploadWxr<WxrImportPlanDto>(file, true),
  })
}

/** Execute the WXR import into the active organization. */
export function useWxrImport(): UseMutationResult<WxrImportResultDto, AppError, File> {
  return useMutation({
    mutationFn: (file: File) => uploadWxr<WxrImportResultDto>(file, false),
  })
}
