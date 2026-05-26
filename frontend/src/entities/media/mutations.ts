import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { MediaDto } from './api-types'
import { mapMediaDtoToModel } from './mapper'
import type { Media } from './model'
import { mediaKeys } from './query-keys'

export function useUploadMedia(): UseMutationResult<Media, AppError, File> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (file: File) => {
      const formData = new FormData()
      formData.append('file', file)
      const dto = await apiClient.upload<MediaDto>('/api/v1/media', formData)
      return mapMediaDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: mediaKeys.list() })
    },
  })
}

export function useDeleteMedia(): UseMutationResult<void, AppError, number> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/media/${String(id)}`)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: mediaKeys.list() })
    },
  })
}
