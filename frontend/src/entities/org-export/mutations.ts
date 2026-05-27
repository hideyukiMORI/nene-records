import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OrgImportResultDto } from './api-types'
import { mapOrgImportResultDtoToModel } from './mapper'
import type { OrgImportResult } from './model'

export function useImportOrg(
  orgId: number,
): UseMutationResult<OrgImportResult, AppError, Record<string, unknown>> {
  return useMutation({
    mutationFn: async (payload) => {
      const dto = await apiClient.post<OrgImportResultDto>(
        `/api/v1/superadmin/organizations/${String(orgId)}/import`,
        payload,
      )
      return mapOrgImportResultDtoToModel(dto)
    },
  })
}
