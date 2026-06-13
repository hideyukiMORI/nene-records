import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { MenuListDto } from './api-types'
import { mapMenuListDtoToModel } from './mapper'
import type { MenuList } from './model'
import { menuKeys } from './query-keys'

export function useMenuList(): UseQueryResult<MenuList, AppError> {
  return useQuery({
    queryKey: menuKeys.adminList(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<MenuListDto>('/api/v1/menus', signal)
      return mapMenuListDtoToModel(dto)
    },
  })
}

export function usePublicMenus(): UseQueryResult<MenuList, AppError> {
  return useQuery({
    queryKey: menuKeys.publicList(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<MenuListDto>('/api/v1/public/menus', signal)
      return mapMenuListDtoToModel(dto)
    },
  })
}
