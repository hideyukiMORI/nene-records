import { useEntityTypeList } from '@/entities/entity-type'

export interface PublicEntityTypeListItem {
  id: number
  slug: string
  name: string
}

export function usePublicBrowseIndexPage() {
  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })

  const items: PublicEntityTypeListItem[] = (entityTypeQuery.data?.items ?? []).map((item) => ({
    id: Number(item.id),
    slug: item.slug,
    name: item.name,
  }))

  return {
    items,
    isLoading: entityTypeQuery.isLoading,
    isError: entityTypeQuery.isError,
    errorTitle: entityTypeQuery.error?.title ?? null,
    refetch: async () => {
      await entityTypeQuery.refetch()
    },
  }
}
