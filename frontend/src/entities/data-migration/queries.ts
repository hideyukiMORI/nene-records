import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { DataMigrationStatusDto } from './api-types'
import { mapDataMigrationStatusDtoToModel } from './mapper'
import type { DataMigrationStatus } from './model'
import { dataMigrationKeys } from './query-keys'

export function useDataMigrationStatus(): UseQueryResult<DataMigrationStatus, AppError> {
  return useQuery({
    queryKey: dataMigrationKeys.status(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<DataMigrationStatusDto>(
        '/api/v1/superadmin/data-migration/status',
        signal,
      )
      return mapDataMigrationStatusDtoToModel(dto)
    },
  })
}
