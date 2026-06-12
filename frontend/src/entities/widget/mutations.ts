import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { WidgetDto } from './api-types'
import { mapWidgetDtoToModel } from './mapper'
import type { Widget, WidgetInput } from './model'
import { widgetKeys } from './query-keys'

function toRequest(input: WidgetInput) {
  return {
    widget_type: input.widgetType,
    region: input.region,
    display_order: input.displayOrder,
    title: input.title,
    settings: input.settings,
  }
}

async function invalidate(queryClient: ReturnType<typeof useQueryClient>): Promise<void> {
  await queryClient.invalidateQueries({ queryKey: widgetKeys.adminList() })
  await queryClient.invalidateQueries({ queryKey: widgetKeys.publicList() })
}

export function useCreateWidget(): UseMutationResult<Widget, AppError, WidgetInput> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<WidgetDto>('/api/v1/widgets', toRequest(input))
      return mapWidgetDtoToModel(dto)
    },
    onSuccess: () => invalidate(queryClient),
  })
}

export function useUpdateWidget(): UseMutationResult<
  Widget,
  AppError,
  { id: number; input: WidgetInput }
> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<WidgetDto>(`/api/v1/widgets/${String(id)}`, toRequest(input))
      return mapWidgetDtoToModel(dto)
    },
    onSuccess: () => invalidate(queryClient),
  })
}

export function useDeleteWidget(): UseMutationResult<void, AppError, number> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/widgets/${String(id)}`)
    },
    onSuccess: () => invalidate(queryClient),
  })
}
