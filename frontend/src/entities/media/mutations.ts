import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { MediaDto } from './api-types'
import { mapMediaDtoToModel } from './mapper'
import type { Media } from './model'

export function useUploadMedia(): UseMutationResult<Media, AppError, File> {
  return useMutation({
    mutationFn: async (file: File) => {
      const formData = new FormData()
      formData.append('file', file)
      const dto = await apiClient.upload<MediaDto>('/api/v1/media', formData)
      return mapMediaDtoToModel(dto)
    },
  })
}
