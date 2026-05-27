import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OrganizationDto } from './api-types'
import { mapCreateInputToDto, mapOrganizationDtoToModel, mapUpdateInputToDto } from './mapper'
import type { CreateOrganizationInput, Organization, UpdateOrganizationInput } from './model'
import { organizationKeys } from './query-keys'

export function useCreateOrganization(): UseMutationResult<
  Organization,
  AppError,
  CreateOrganizationInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<OrganizationDto>(
        '/api/v1/organizations',
        mapCreateInputToDto(input),
      )
      return mapOrganizationDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: organizationKeys.lists() })
    },
  })
}

export function useUpdateOrganization(): UseMutationResult<
  Organization,
  AppError,
  { id: number; input: UpdateOrganizationInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.patch<OrganizationDto>(
        `/api/v1/organizations/${String(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapOrganizationDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({ queryKey: organizationKeys.lists() })
      await queryClient.invalidateQueries({ queryKey: organizationKeys.detail(variables.id) })
    },
  })
}

export function useDeleteOrganization(): UseMutationResult<void, AppError, number> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/organizations/${String(id)}`)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: organizationKeys.lists() })
    },
  })
}
