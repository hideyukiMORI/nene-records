import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, type AppError } from '@/shared/api/client'
import type { ThemeDto, ThemeManifestDto } from './api-types'

/** Register a new runtime theme from a full manifest. The server validates and may 422. */
export function useCreateTheme(): UseMutationResult<ThemeDto, AppError, ThemeManifestDto> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (manifest: ThemeManifestDto) =>
      apiClient.post<ThemeDto>('/api/v1/themes', manifest),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['themes'] })
    },
  })
}

/** Delete a runtime theme by key. Invalidates the theme lists on success. */
export function useDeleteTheme(): UseMutationResult<undefined, AppError, string> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (themeKey: string) =>
      apiClient.delete(`/api/v1/themes/${encodeURIComponent(themeKey)}`),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['themes'] })
    },
  })
}

/** Replace a runtime theme's manifest. The server re-validates and may 422. */
export function useUpdateTheme(): UseMutationResult<
  ThemeDto,
  AppError,
  { key: string; manifest: ThemeManifestDto }
> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ key, manifest }) =>
      apiClient.put<ThemeDto>(`/api/v1/themes/${encodeURIComponent(key)}`, manifest),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['themes'] })
    },
  })
}
