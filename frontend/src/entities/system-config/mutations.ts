import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { SystemConfigDto } from './api-types'
import { mapSystemConfigDtoToModel, mapUpdateInputToDto } from './mapper'
import type { SystemConfig, UpdateSystemConfigInput } from './model'
import { systemConfigKeys } from './query-keys'

export function useUpdateSystemConfig(): UseMutationResult<
  SystemConfig,
  Error,
  UpdateSystemConfigInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.patch<SystemConfigDto>(
        '/api/v1/superadmin/system-config',
        mapUpdateInputToDto(input),
      )
      return mapSystemConfigDtoToModel(dto)
    },
    onSuccess: (data) => {
      queryClient.setQueryData(systemConfigKeys.detail(), data)
    },
  })
}
