import { useCallback, useState } from 'react'
import {
  useCreateTag,
  useDeleteTag,
  useTagList,
  useUpdateTag,
  type Tag,
  type TagId,
} from '@/entities/tag'
import type { CreateTagFormValues } from './use-create-tag-form'

export function useManageTagsPage() {
  const listQuery = useTagList()
  const createMutation = useCreateTag()
  const updateMutation = useUpdateTag()
  const deleteMutation = useDeleteTag()
  const [editTarget, setEditTarget] = useState<Tag | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<Tag | null>(null)

  const createTag = useCallback(
    async (values: CreateTagFormValues) => {
      await createMutation.mutateAsync(values)
    },
    [createMutation],
  )

  const requestEdit = useCallback((tag: Tag) => {
    setEditTarget(tag)
  }, [])

  const cancelEdit = useCallback(() => {
    setEditTarget(null)
  }, [])

  const updateTag = useCallback(
    async (values: CreateTagFormValues) => {
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

  const requestDelete = useCallback((tag: Tag) => {
    setDeleteTarget(tag)
  }, [])

  const cancelDelete = useCallback(() => {
    setDeleteTarget(null)
  }, [])

  const confirmDelete = useCallback(async () => {
    if (deleteTarget === null) {
      return
    }

    const id: TagId = deleteTarget.id
    await deleteMutation.mutateAsync(id)
    setDeleteTarget(null)
  }, [deleteMutation, deleteTarget])

  return {
    items: listQuery.data?.items ?? [],
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    errorTitle: listQuery.error?.title ?? null,
    refetch: listQuery.refetch,
    createTag,
    isCreating: createMutation.isPending,
    createErrorTitle: createMutation.error?.title ?? null,
    editTarget,
    requestEdit,
    cancelEdit,
    updateTag,
    isUpdating: updateMutation.isPending,
    updateErrorTitle: updateMutation.error?.title ?? null,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting: deleteMutation.isPending,
  }
}
