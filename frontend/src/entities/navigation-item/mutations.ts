import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { NavigationItemDto } from './api-types'
import { mapNavigationItemDtoToModel } from './mapper'
import type { CreateNavigationItemInput, NavigationItem, UpdateNavigationItemInput } from './model'
import { navigationItemKeys } from './query-keys'

export function useCreateNavigationItem(): UseMutationResult<
  NavigationItem,
  AppError,
  CreateNavigationItemInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<NavigationItemDto>('/api/v1/navigation-items', {
        label: input.label,
        url: input.url,
        ...(input.menuId !== undefined ? { menu_id: input.menuId } : {}),
        display_order: input.displayOrder,
      })
      return mapNavigationItemDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: navigationItemKeys.adminList() })
      await queryClient.invalidateQueries({ queryKey: navigationItemKeys.publicList() })
    },
  })
}

export function useUpdateNavigationItem(): UseMutationResult<
  NavigationItem,
  AppError,
  { id: number; input: UpdateNavigationItemInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<NavigationItemDto>(`/api/v1/navigation-items/${String(id)}`, {
        label: input.label,
        url: input.url,
        ...(input.menuId !== undefined ? { menu_id: input.menuId } : {}),
        display_order: input.displayOrder,
      })
      return mapNavigationItemDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: navigationItemKeys.adminList() })
      await queryClient.invalidateQueries({ queryKey: navigationItemKeys.publicList() })
    },
  })
}

export function useDeleteNavigationItem(): UseMutationResult<void, AppError, number> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/navigation-items/${String(id)}`)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: navigationItemKeys.adminList() })
      await queryClient.invalidateQueries({ queryKey: navigationItemKeys.publicList() })
    },
  })
}
