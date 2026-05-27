import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OrganizationDto, OrganizationListDto } from './api-types'
import { mapOrganizationDtoToModel, mapOrganizationListDtoToModel } from './mapper'
import type { Organization, OrganizationList } from './model'
import { organizationKeys } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 50, offset: 0 } as const

export function useOrganizationList(
  params: { limit: number; offset: number } = DEFAULT_LIST_PARAMS,
): UseQueryResult<OrganizationList, AppError> {
  return useQuery({
    queryKey: organizationKeys.list(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      const dto = await apiClient.get<OrganizationListDto>(
        `/api/v1/organizations?${search.toString()}`,
        signal,
      )
      return mapOrganizationListDtoToModel(dto)
    },
  })
}

export function useOrganization(id: number): UseQueryResult<Organization, AppError> {
  return useQuery({
    queryKey: organizationKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<OrganizationDto>(
        `/api/v1/organizations/${String(id)}`,
        signal,
      )
      return mapOrganizationDtoToModel(dto)
    },
    enabled: id > 0,
  })
}
