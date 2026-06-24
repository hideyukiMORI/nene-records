import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { components } from '@/shared/api/schema.gen'

// Types are derived from the OpenAPI contract (docs/openapi/openapi.yaml) via codegen.
export type WxrPlannedItem = components['schemas']['WxrPlannedItem']
export type WxrSkippedItem = components['schemas']['WxrSkippedItem']
/** Dry-run preview returned by POST /api/v1/migration/wxr (dry_run=true). */
export type WxrImportPlanDto = components['schemas']['WxrImportPlanResponse']
/** Result returned when executed (dry_run=false). */
export type WxrImportResultDto = components['schemas']['WxrImportResultResponse']

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
