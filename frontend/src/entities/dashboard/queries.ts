import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { DashboardSummaryDto } from './api-types'
import { mapDashboardSummaryDtoToModel } from './mapper'
import type { DashboardSummary } from './model'
import { dashboardKeys } from './query-keys'

export function useDashboardSummary(): UseQueryResult<DashboardSummary, AppError> {
  return useQuery({
    queryKey: dashboardKeys.summary(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<DashboardSummaryDto>('/api/v1/dashboard', signal)
      return mapDashboardSummaryDtoToModel(dto)
    },
  })
}
