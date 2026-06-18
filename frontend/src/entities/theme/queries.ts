import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, type AppError } from '@/shared/api/client'
import type { ThemeListDto } from './api-types'

/**
 * Public list of runtime (data-driven) themes. The public site applies the
 * active runtime theme's manifest as a scoped stylesheet (see runtime-themes).
 */
export function usePublicThemes(): UseQueryResult<ThemeListDto, AppError> {
  return useQuery({
    queryKey: ['themes', 'public'],
    queryFn: async ({ signal }) => apiClient.get<ThemeListDto>('/api/v1/public/themes', signal),
  })
}

/** Admin list of runtime themes (auth) — for the theme picker / management. */
export function useThemes(): UseQueryResult<ThemeListDto, AppError> {
  return useQuery({
    queryKey: ['themes', 'admin'],
    queryFn: async ({ signal }) => apiClient.get<ThemeListDto>('/api/v1/themes', signal),
  })
}
