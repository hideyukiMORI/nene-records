import { useEntityTypeList, type EntityType } from '@/entities/entity-type'

export interface PermalinkSettingsPageState {
  entityTypes: EntityType[]
  isLoading: boolean
  onRefresh: () => void
}

export function usePermalinkSettingsPage(): PermalinkSettingsPageState {
  const listQuery = useEntityTypeList({ limit: 100, offset: 0 })

  return {
    entityTypes: listQuery.data?.items ?? [],
    isLoading: listQuery.isLoading,
    onRefresh: () => {
      void listQuery.refetch()
    },
  }
}
