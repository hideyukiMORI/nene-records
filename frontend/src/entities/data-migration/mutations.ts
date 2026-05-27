import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { AssignOrgResultDto } from './api-types'
import { mapAssignOrgResultDtoToModel } from './mapper'
import type { AssignOrgResult } from './model'
import { dataMigrationKeys } from './query-keys'

export function useAssignOrg(): UseMutationResult<
  AssignOrgResult,
  AppError,
  { targetOrgId: number }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ targetOrgId }) => {
      const dto = await apiClient.post<AssignOrgResultDto>(
        '/api/v1/superadmin/data-migration/assign-org',
        { target_org_id: targetOrgId },
      )
      return mapAssignOrgResultDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: dataMigrationKeys.status() })
    },
  })
}
