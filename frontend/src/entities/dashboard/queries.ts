import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { AccessStatsByDateDto, DashboardSummaryDto } from './api-types'
import { mapAccessStatsByDateDtoToModel, mapDashboardSummaryDtoToModel } from './mapper'
import type { AccessStatsByDate, DashboardSummary } from './model'
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

/** Daily access counts over an inclusive `from`..`to` range (both `YYYY-MM-DD`). */
export function useAccessStats(
  from: string,
  to: string,
): UseQueryResult<AccessStatsByDate, AppError> {
  return useQuery({
    queryKey: dashboardKeys.accessStats(from, to),
    queryFn: async ({ signal }) => {
      const query = new URLSearchParams({ from, to }).toString()
      const dto = await apiClient.get<AccessStatsByDateDto>(
        `/api/v1/analytics/access-stats?${query}`,
        signal,
      )
      return mapAccessStatsByDateDtoToModel(dto)
    },
  })
}
