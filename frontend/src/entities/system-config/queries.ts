import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { SystemConfigDto } from './api-types'
import { mapSystemConfigDtoToModel } from './mapper'
import type { SystemConfig } from './model'
import { systemConfigKeys } from './query-keys'

export function useSystemConfig(): UseQueryResult<SystemConfig> {
  return useQuery({
    queryKey: systemConfigKeys.detail(),
    queryFn: async () => {
      const dto = await apiClient.get<SystemConfigDto>('/api/v1/superadmin/system-config')
      return mapSystemConfigDtoToModel(dto)
    },
  })
}
