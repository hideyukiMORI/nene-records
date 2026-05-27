import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '@/shared/api/client'
import type { SystemConfigDto } from './api-types'

const QUERY_KEY = ['system-config'] as const

export function useSystemConfig() {
  return useQuery<SystemConfigDto>({
    queryKey: QUERY_KEY,
    queryFn: () => apiClient.get<SystemConfigDto>('/api/v1/superadmin/system-config'),
  })
}

export function useUpdateSystemConfig() {
  const queryClient = useQueryClient()
  return useMutation<SystemConfigDto, Error, Partial<SystemConfigDto>>({
    mutationFn: (input) =>
      apiClient.patch<SystemConfigDto>('/api/v1/superadmin/system-config', input),
    onSuccess: (data) => {
      queryClient.setQueryData(QUERY_KEY, data)
    },
  })
}
