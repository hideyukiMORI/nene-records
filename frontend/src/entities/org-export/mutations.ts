import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OrgImportResultDto } from './api-types'

export function useImportOrg(
  orgId: number,
): UseMutationResult<OrgImportResultDto, AppError, Record<string, unknown>> {
  return useMutation({
    mutationFn: async (payload) => {
      return apiClient.post<OrgImportResultDto>(
        `/api/v1/superadmin/organizations/${String(orgId)}/import`,
        payload,
      )
    },
  })
}
