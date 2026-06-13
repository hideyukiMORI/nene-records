import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { MenuDto } from './api-types'
import { mapMenuDtoToModel } from './mapper'
import type { CreateMenuInput, Menu, UpdateMenuInput } from './model'
import { menuKeys } from './query-keys'

async function invalidate(queryClient: ReturnType<typeof useQueryClient>): Promise<void> {
  await queryClient.invalidateQueries({ queryKey: menuKeys.all })
}

export function useCreateMenu(): UseMutationResult<Menu, AppError, CreateMenuInput> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<MenuDto>('/api/v1/menus', {
        name: input.name,
        location: input.location,
      })
      return mapMenuDtoToModel(dto)
    },
    onSuccess: () => invalidate(queryClient),
  })
}

export function useUpdateMenu(): UseMutationResult<
  Menu,
  AppError,
  { id: number; input: UpdateMenuInput }
> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<MenuDto>(`/api/v1/menus/${String(id)}`, {
        name: input.name,
        location: input.location,
      })
      return mapMenuDtoToModel(dto)
    },
    onSuccess: () => invalidate(queryClient),
  })
}

export function useDeleteMenu(): UseMutationResult<void, AppError, number> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/menus/${String(id)}`)
    },
    onSuccess: () => invalidate(queryClient),
  })
}
