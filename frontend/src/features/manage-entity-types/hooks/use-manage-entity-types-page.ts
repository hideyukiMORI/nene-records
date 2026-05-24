import { useCallback, useState } from 'react'
import {
  useCreateEntityType,
  useDeleteEntityType,
  useEntityTypeList,
  type EntityType,
  type EntityTypeId,
} from '@/entities/entity-type'
import type { CreateEntityTypeFormValues } from './use-create-entity-type-form'

export function useManageEntityTypesPage() {
  const listQuery = useEntityTypeList()
  const createMutation = useCreateEntityType()
  const deleteMutation = useDeleteEntityType()
  const [deleteTarget, setDeleteTarget] = useState<EntityType | null>(null)

  const createEntityType = useCallback(
    async (values: CreateEntityTypeFormValues) => {
      await createMutation.mutateAsync(values)
    },
    [createMutation],
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
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting: deleteMutation.isPending,
  }
}
