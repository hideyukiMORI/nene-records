import { useCallback, useState } from 'react'
import {
  useCreateEntityType,
  useDeleteEntityType,
  useEntityTypeList,
  useReorderEntityTypes,
  useUpdateEntityType,
  type EntityType,
  type EntityTypeId,
} from '@/entities/entity-type'
import {
  formValuesToLabels,
  type CreateEntityTypeFormValues,
  type EditEntityTypeFormValues,
} from './use-create-entity-type-form'

export function useManageEntityTypesPage() {
  const listQuery = useEntityTypeList()
  const createMutation = useCreateEntityType()
  const updateMutation = useUpdateEntityType()
  const deleteMutation = useDeleteEntityType()
  const reorderMutation = useReorderEntityTypes()
  const [editTarget, setEditTarget] = useState<EntityType | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<EntityType | null>(null)

  const createEntityType = useCallback(
    async (values: CreateEntityTypeFormValues) => {
      await createMutation.mutateAsync(values)
    },
    [createMutation],
  )

  const requestEdit = useCallback((entityType: EntityType) => {
    setEditTarget(entityType)
  }, [])

  const cancelEdit = useCallback(() => {
    setEditTarget(null)
  }, [])

  const updateEntityType = useCallback(
    async (values: EditEntityTypeFormValues) => {
      if (editTarget === null) {
        return
      }

      const labels = formValuesToLabels(values)

      // Normalize: empty string → null (use backend default)
      const rawPermalink = values.permalinkPattern
      const permalinkPattern = rawPermalink === '' || rawPermalink == null ? null : rawPermalink

      await updateMutation.mutateAsync({
        id: editTarget.id,
        input: {
          name: values.name,
          slug: values.slug,
          isPinned: values.isPinned,
          ...(Object.keys(labels).length > 0 ? { labels } : {}),
          permalinkPattern,
          defaultLayout: values.defaultLayout,
        },
      })
      setEditTarget(null)
    },
    [editTarget, updateMutation],
  )

  const requestDelete = useCallback(
    (entityType: EntityType) => {
      deleteMutation.reset()
      setDeleteTarget(entityType)
    },
    [deleteMutation],
  )

  const cancelDelete = useCallback(() => {
    deleteMutation.reset()
    setDeleteTarget(null)
  }, [deleteMutation])

  // Move a type one slot up/down and persist the whole order.
  const moveEntityType = useCallback(
    async (id: EntityTypeId, direction: 'up' | 'down') => {
      const ids = (listQuery.data?.items ?? []).map((item) => item.id)
      const from = ids.indexOf(id)
      if (from === -1) {
        return
      }
      const to = direction === 'up' ? from - 1 : from + 1
      if (to < 0 || to >= ids.length) {
        return
      }
      const next = [...ids]
      const [removed] = next.splice(from, 1)
      if (removed === undefined) {
        return
      }
      next.splice(to, 0, removed)
      await reorderMutation.mutateAsync(next)
    },
    [listQuery.data, reorderMutation],
  )

  const confirmDelete = useCallback(async () => {
    if (deleteTarget === null) {
      return
    }

    const id: EntityTypeId = deleteTarget.id
    await deleteMutation.mutateAsync(id)
    setDeleteTarget(null)
  }, [deleteMutation, deleteTarget])

  return {
    items: listQuery.data?.items ?? [],
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    errorTitle: listQuery.error?.title ?? null,
    refetch: listQuery.refetch,
    createEntityType,
    isCreating: createMutation.isPending,
    createErrorTitle: createMutation.error?.title ?? null,
    editTarget,
    requestEdit,
    cancelEdit,
    updateEntityType,
    isUpdating: updateMutation.isPending,
    updateErrorTitle: updateMutation.error?.title ?? null,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting: deleteMutation.isPending,
    deleteErrorDetail: deleteMutation.error?.detail ?? deleteMutation.error?.title ?? null,
    moveEntityType,
    isReordering: reorderMutation.isPending,
  }
}
