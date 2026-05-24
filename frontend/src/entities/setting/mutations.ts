import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { SettingValueDto } from './api-types'
import { mapSettingValueDtoToModel, mapUpdateSettingInputToDto } from './mapper'
import type { SettingKey, UpdateSettingInput } from './model'
import { settingKeys } from './query-keys'

export function useUpdateSetting(): UseMutationResult<
  { settingKey: SettingKey; value: string; updatedAt: string | null },
  AppError,
  { settingKey: SettingKey; input: UpdateSettingInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ settingKey, input }) => {
      const dto = await apiClient.put<SettingValueDto>(
        `/api/v1/settings/${encodeURIComponent(settingKey)}`,
        mapUpdateSettingInputToDto(input),
      )
      return mapSettingValueDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({ queryKey: settingKeys.adminList() })
      await queryClient.invalidateQueries({ queryKey: settingKeys.publicList() })
      await queryClient.invalidateQueries({ queryKey: settingKeys.revisions(variables.settingKey) })
    },
  })
}
