import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { WidgetListDto } from './api-types'
import { mapWidgetListDtoToModel } from './mapper'
import type { WidgetList } from './model'
import { widgetKeys } from './query-keys'

export function useWidgetList(): UseQueryResult<WidgetList, AppError> {
  return useQuery({
    queryKey: widgetKeys.adminList(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<WidgetListDto>('/api/v1/widgets', signal)
      return mapWidgetListDtoToModel(dto)
    },
  })
}

export function usePublicWidgets(): UseQueryResult<WidgetList, AppError> {
  return useQuery({
    queryKey: widgetKeys.publicList(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<WidgetListDto>('/api/v1/public/widgets', signal)
      return mapWidgetListDtoToModel(dto)
    },
  })
}
