import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { components } from '@/shared/api/schema.gen'

/** Result of POST /api/v1/migration/url-redirects (preview or import). */
export type RedirectCsvImportDto = components['schemas']['RedirectCsvImportResponse']

const ENDPOINT = '/api/v1/migration/url-redirects'

function uploadCsv(file: File, dryRun: boolean): Promise<RedirectCsvImportDto> {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('dry_run', dryRun ? 'true' : 'false')
  return apiClient.upload<RedirectCsvImportDto>(ENDPOINT, formData)
}

/** Preview (dry-run) a CSV redirect import without writing. */
export function useRedirectCsvPreview(): UseMutationResult<RedirectCsvImportDto, AppError, File> {
  return useMutation({
    mutationFn: (file: File) => uploadCsv(file, true),
  })
}

/** Execute the CSV redirect import into the active organization. */
export function useRedirectCsvImport(): UseMutationResult<RedirectCsvImportDto, AppError, File> {
  return useMutation({
    mutationFn: (file: File) => uploadCsv(file, false),
  })
}
