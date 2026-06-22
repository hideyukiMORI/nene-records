import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type {
  EntityDto,
  GeneratePreviewTokenResponseDto,
  ScheduleEntityResponseDto,
} from './api-types'
import { type EntityId, toEntityId } from './ids'
import {
  mapCreateInputToDto,
  mapEntityDtoToModel,
  mapGeneratePreviewTokenResponseDtoToOutput,
  mapScheduleResponseDtoToOutput,
  mapUpdateInputToDto,
} from './mapper'
import type {
  CreateEntityInput,
  Entity,
  GeneratePreviewTokenInput,
  GeneratePreviewTokenOutput,
  RevokePreviewTokenInput,
  ScheduleEntityInput,
  ScheduleEntityOutput,
  UpdateEntityInput,
} from './model'
import { entityKeys } from './query-keys'

export function useCreateEntity(): UseMutationResult<Entity, AppError, CreateEntityInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<EntityDto>('/api/v1/entities', mapCreateInputToDto(input))
      return mapEntityDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: entityKeys.lists(),
        predicate: (query) => {
          const params = query.queryKey[2]
          return (
            typeof params === 'object' &&
            params !== null &&
            'entityTypeId' in params &&
            params.entityTypeId === variables.entityTypeId
          )
        },
      })
    },
  })
}

export function useUpdateEntity(): UseMutationResult<Entity, AppError, UpdateEntityInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.put<EntityDto>(
        `/api/v1/entities/${String(input.id)}`,
        mapUpdateInputToDto(input),
      )
      return mapEntityDtoToModel(dto)
    },
    onSuccess: async (data) => {
      queryClient.setQueryData(entityKeys.detail(data.id), data)
      await queryClient.invalidateQueries({ queryKey: entityKeys.lists() })
    },
  })
}

export function useDeleteEntity(): UseMutationResult<
  void,
  AppError,
  { id: EntityId; entityTypeId: number }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id }) => {
      await apiClient.delete(`/api/v1/entities/${String(id)}`)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: entityKeys.lists(),
        predicate: (query) => {
          const params = query.queryKey[2]
          return (
            typeof params === 'object' &&
            params !== null &&
            'entityTypeId' in params &&
            params.entityTypeId === variables.entityTypeId
          )
        },
      })
    },
  })
}

export function useScheduleEntity(): UseMutationResult<
  ScheduleEntityOutput,
  AppError,
  ScheduleEntityInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<ScheduleEntityResponseDto>(
        `/api/v1/entities/${String(input.id)}/schedule`,
        { scheduled_at: input.scheduledAt },
      )
      return mapScheduleResponseDtoToOutput(dto)
    },
    onSuccess: async (_data, variables) => {
      queryClient.removeQueries({ queryKey: entityKeys.detail(toEntityId(variables.id)) })
      await queryClient.invalidateQueries({ queryKey: entityKeys.lists() })
    },
  })
}

export function useUnscheduleEntity(): UseMutationResult<void, AppError, { id: EntityId }> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id }) => {
      await apiClient.delete(`/api/v1/entities/${String(id)}/schedule`)
    },
    onSuccess: async (_data, variables) => {
      queryClient.removeQueries({ queryKey: entityKeys.detail(variables.id) })
      await queryClient.invalidateQueries({ queryKey: entityKeys.lists() })
    },
  })
}

export function useGeneratePreviewToken(): UseMutationResult<
  GeneratePreviewTokenOutput,
  AppError,
  GeneratePreviewTokenInput
> {
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<GeneratePreviewTokenResponseDto>(
        `/api/v1/entities/${String(input.id)}/preview-token`,
        {},
      )
      return mapGeneratePreviewTokenResponseDtoToOutput(dto)
    },
  })
}

export function useRevokePreviewToken(): UseMutationResult<
  void,
  AppError,
  RevokePreviewTokenInput
> {
  return useMutation({
    mutationFn: async (input) => {
      await apiClient.delete(`/api/v1/entities/${String(input.id)}/preview-token`)
    },
  })
}
