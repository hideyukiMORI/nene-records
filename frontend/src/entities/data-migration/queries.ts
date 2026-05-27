import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { DataMigrationStatusDto } from './api-types'

export const dataMigrationKeys = {
  status: () => ['data-migration', 'status'] as const,
}

export function useDataMigrationStatus(): UseQueryResult<DataMigrationStatusDto, AppError> {
  return useQuery({
    queryKey: dataMigrationKeys.status(),
    queryFn: async ({ signal }) => {
      return apiClient.get<DataMigrationStatusDto>(
        '/api/v1/superadmin/data-migration/status',
        signal,
      )
    },
  })
}
