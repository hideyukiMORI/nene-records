import { useCallback, useState } from 'react'
import {
  useCreateEntityType,
  useDeleteEntityType,
  useEntityTypeList,
  useUpdateEntityType,
  type EntityType,
  type EntityTypeId,
} from '@/entities/entity-type'
import type { CreateEntityTypeFormValues } from './use-create-entity-type-form'

export function useManageEntityTypesPage() {
  const listQuery = useEntityTypeList()
  const createMutation = useCreateEntityType()
  const updateMutation = useUpdateEntityType()
  const deleteMutation = useDeleteEntityType()
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
    async (values: CreateEntityTypeFormValues) => {
      if (editTarget === null) {
        return
      }

      await updateMutation.mutateAsync({
        id: editTarget.id,
        input: values,
      })
      setEditTarget(null)
    },
    [editTarget, updateMutation],
  )

  const requestDelete = useCallback((entityType: EntityType) => {
    setDeleteTarget(entityType)
  }, [])

  const cancelDelete = useCallback(() => {
    setDeleteTarget(null)
  }, [])

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
  }
}
