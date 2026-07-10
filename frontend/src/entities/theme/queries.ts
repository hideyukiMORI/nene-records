import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, type AppError } from '@/shared/api/client'
import type { ThemeAuthoringGuideDto, ThemeListDto } from './api-types'
import { themeKeys } from './query-keys'

/**
 * Public list of runtime (data-driven) themes. The public site applies the
 * active runtime theme's manifest as a scoped stylesheet (see runtime-themes).
 */
export function usePublicThemes(): UseQueryResult<ThemeListDto, AppError> {
  return useQuery({
    queryKey: themeKeys.publicList(),
    queryFn: async ({ signal }) => apiClient.get<ThemeListDto>('/api/v1/public/themes', signal),
  })
}

/** Admin list of runtime themes (auth) — for the theme picker / management. */
export function useThemes(): UseQueryResult<ThemeListDto, AppError> {
  return useQuery({
    queryKey: themeKeys.adminList(),
    queryFn: async ({ signal }) => apiClient.get<ThemeListDto>('/api/v1/themes', signal),
  })
}

/**
 * The theme authoring guide — the customizer's advanced token section reads its
 * `renderModel.optionalTokens` catalog (#785), so the documented engine tokens
 * drive the UI without a hand-maintained copy.
 */
export function useThemeAuthoringGuide(): UseQueryResult<ThemeAuthoringGuideDto, AppError> {
  return useQuery({
    queryKey: themeKeys.authoringGuide(),
    queryFn: async ({ signal }) =>
      apiClient.get<ThemeAuthoringGuideDto>('/api/v1/themes/authoring-guide', signal),
    staleTime: Infinity,
  })
}
