import { useCallback, useState } from 'react'
import {
  useCreateNavigationItem,
  useDeleteNavigationItem,
  useNavigationItemList,
  useUpdateNavigationItem,
  type NavigationItem,
} from '@/entities/navigation-item'

export interface NavigationItemFormValues {
  label: string
  url: string
  displayOrder: number
}

export function useManageNavigationPage() {
  const listQuery = useNavigationItemList()
  const createMutation = useCreateNavigationItem()
  const updateMutation = useUpdateNavigationItem()
  const deleteMutation = useDeleteNavigationItem()
  const [editTarget, setEditTarget] = useState<NavigationItem | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<NavigationItem | null>(null)

  const createItem = useCallback(
    async (values: NavigationItemFormValues) => {
      await createMutation.mutateAsync({
        label: values.label,
        url: values.url,
        displayOrder: values.displayOrder,
      })
    },
    [createMutation],
  )

  const requestEdit = useCallback((item: NavigationItem) => {
    setEditTarget(item)
  }, [])

  const cancelEdit = useCallback(() => {
    setEditTarget(null)
  }, [])

  const updateItem = useCallback(
    async (values: NavigationItemFormValues) => {
      if (editTarget === null) {
        return
      }

      await updateMutation.mutateAsync({
        id: editTarget.id,
        input: {
          label: values.label,
          url: values.url,
          displayOrder: values.displayOrder,
        },
      })
      setEditTarget(null)
    },
    [editTarget, updateMutation],
  )

  const requestDelete = useCallback((item: NavigationItem) => {
    setDeleteTarget(item)
  }, [])

  const cancelDelete = useCallback(() => {
    setDeleteTarget(null)
  }, [])

  const confirmDelete = useCallback(async () => {
    if (deleteTarget === null) {
      return
    }

    await deleteMutation.mutateAsync(deleteTarget.id)
    setDeleteTarget(null)
  }, [deleteMutation, deleteTarget])

  return {
    items: listQuery.data?.items ?? [],
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    errorTitle: listQuery.error?.title ?? null,
    refetch: listQuery.refetch,
    createItem,
    isCreating: createMutation.isPending,
    createErrorTitle: createMutation.error?.title ?? null,
    editTarget,
    requestEdit,
    cancelEdit,
    updateItem,
    isUpdating: updateMutation.isPending,
    updateErrorTitle: updateMutation.error?.title ?? null,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting: deleteMutation.isPending,
  }
}
