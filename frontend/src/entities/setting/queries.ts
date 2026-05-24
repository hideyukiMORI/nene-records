import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { PublicSettingListDto, SettingListDto, SettingRevisionListDto } from './api-types'
import {
  mapPublicSettingListDtoToModel,
  mapSettingListDtoToModel,
  mapSettingRevisionListDtoToModel,
} from './mapper'
import type { PublicSettingList, SettingList, SettingRevisionList } from './model'
import { settingKeys } from './query-keys'

const DEFAULT_REVISION_PARAMS = { limit: 20, offset: 0 } as const

export function useSettingList(): UseQueryResult<SettingList, AppError> {
  return useQuery({
    queryKey: settingKeys.adminList(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<SettingListDto>('/api/v1/settings', signal)
      return mapSettingListDtoToModel(dto)
    },
  })
}

export function usePublicSettings(): UseQueryResult<PublicSettingList, AppError> {
  return useQuery({
    queryKey: settingKeys.publicList(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<PublicSettingListDto>('/api/v1/public/settings', signal)
      return mapPublicSettingListDtoToModel(dto)
    },
  })
}

export function useSettingRevisions(
  settingKey: string,
  params: { limit: number; offset: number } = DEFAULT_REVISION_PARAMS,
): UseQueryResult<SettingRevisionList, AppError> {
  return useQuery({
    queryKey: [...settingKeys.revisions(settingKey), params],
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      const dto = await apiClient.get<SettingRevisionListDto>(
        `/api/v1/settings/${encodeURIComponent(settingKey)}/revisions?${search.toString()}`,
        signal,
      )
      return mapSettingRevisionListDtoToModel(dto)
    },
    enabled: settingKey !== '',
  })
}
