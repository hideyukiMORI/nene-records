import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { AssignOrgResultDto } from './api-types'
import { dataMigrationKeys } from './queries'

export function useAssignOrg(): UseMutationResult<
  AssignOrgResultDto,
  AppError,
  { targetOrgId: number }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ targetOrgId }) => {
      return apiClient.post<AssignOrgResultDto>('/api/v1/superadmin/data-migration/assign-org', {
        target_org_id: targetOrgId,
      })
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: dataMigrationKeys.status() })
    },
  })
}
