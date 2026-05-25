import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { NavigationItemListDto } from './api-types'
import { mapNavigationItemListDtoToModel } from './mapper'
import type { NavigationItemList } from './model'
import { navigationItemKeys } from './query-keys'

export function useNavigationItemList(): UseQueryResult<NavigationItemList, AppError> {
  return useQuery({
    queryKey: navigationItemKeys.adminList(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<NavigationItemListDto>('/api/v1/navigation-items', signal)
      return mapNavigationItemListDtoToModel(dto)
    },
  })
}

export function usePublicNavigationItems(): UseQueryResult<NavigationItemList, AppError> {
  return useQuery({
    queryKey: navigationItemKeys.publicList(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<NavigationItemListDto>(
        '/api/v1/public/navigation-items',
        signal,
      )
      return mapNavigationItemListDtoToModel(dto)
    },
  })
}
