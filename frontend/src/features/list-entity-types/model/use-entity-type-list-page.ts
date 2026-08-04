import { useEntityTypeList, type EntityType } from '@/entities/entity-type'

export function useEntityTypeListPage() {
  const query = useEntityTypeList()

  return {
    items: query.data?.items ?? [],
    isLoading: query.isLoading,
    isError: query.isError,
    errorTitle: query.error?.title ?? null,
    refetch: query.refetch,
  }
}

export type EntityTypeListItem = EntityType
