import { useCallback, useMemo, useState } from 'react'
import {
  defaultEntityListParams,
  useCreateEntity,
  useDeleteEntity,
  useEntityList,
  type Entity,
  type EntityId,
} from '@/entities/entity'
import { useTagList } from '@/entities/tag'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'

export function useManageEntitiesPage(entityTypeId: number) {
  const [selectedTagSlugs, setSelectedTagSlugs] = useState<string[]>([])
  const listParams = useMemo(
    () => defaultEntityListParams(entityTypeId, selectedTagSlugs),
    [entityTypeId, selectedTagSlugs],
  )
  const listQuery = useEntityList(listParams)
  const tagListQuery = useTagList({ limit: 100, offset: 0 })
  const textFieldQuery = useTextFieldList(defaultTextFieldListParamsForEntityType(entityTypeId))
  const createMutation = useCreateEntity()
  const deleteMutation = useDeleteEntity()
  const [deleteTarget, setDeleteTarget] = useState<Entity | null>(null)

  const items = useMemo(() => listQuery.data?.items ?? [], [listQuery.data?.items])

  const recordLabels = useMemo((): Record<string, string> => {
    const textFields = textFieldQuery.data?.items ?? []

    return Object.fromEntries(
      items.map((entity) => [
        String(entity.id),
        getRecordDisplayLabel(Number(entity.id), textFields, `Record #${String(entity.id)}`),
      ]),
    )
  }, [items, textFieldQuery.data?.items])

  const toggleTagSlug = useCallback((slug: string) => {
    setSelectedTagSlugs((current) =>
      current.includes(slug) ? current.filter((item) => item !== slug) : [...current, slug],
    )
  }, [])

  const clearTagFilter = useCallback(() => {
    setSelectedTagSlugs([])
  }, [])

  const createEntity = useCallback(async () => {
    await createMutation.mutateAsync({ entityTypeId })
  }, [createMutation, entityTypeId])

  const requestDelete = useCallback((entity: Entity) => {
    setDeleteTarget(entity)
  }, [])

  const cancelDelete = useCallback(() => {
    setDeleteTarget(null)
  }, [])

  const confirmDelete = useCallback(async () => {
    if (deleteTarget === null) {
      return
    }

    const id: EntityId = deleteTarget.id
    await deleteMutation.mutateAsync({ id, entityTypeId })
    setDeleteTarget(null)
  }, [deleteMutation, deleteTarget, entityTypeId])

  const isLoading = listQuery.isLoading || textFieldQuery.isLoading
  const isError = listQuery.isError || textFieldQuery.isError
  const errorTitle = listQuery.error?.title ?? textFieldQuery.error?.title ?? null

  return {
    items,
    recordLabels,
    total: listQuery.data?.total ?? 0,
    availableTags: tagListQuery.data?.items ?? [],
    selectedTagSlugs,
    toggleTagSlug,
    clearTagFilter,
    isFilterActive: selectedTagSlugs.length > 0,
    isLoading,
    isError,
    errorTitle,
    refetch: async () => {
      await Promise.all([listQuery.refetch(), textFieldQuery.refetch(), tagListQuery.refetch()])
    },
    createEntity,
    isCreating: createMutation.isPending,
    createErrorTitle: createMutation.error?.title ?? null,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting: deleteMutation.isPending,
  }
}
