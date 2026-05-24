import { useCallback, useState } from 'react'
import {
  defaultFieldDefListParams,
  useCreateFieldDef,
  useDeleteFieldDef,
  useFieldDefList,
  type FieldDef,
  type FieldDefId,
} from '@/entities/field-def'
import type { CreateFieldDefFormValues } from './use-create-field-def-form'

export function useManageFieldDefsPage(entityTypeId: number) {
  const listParams = defaultFieldDefListParams(entityTypeId)
  const listQuery = useFieldDefList(listParams)
  const createMutation = useCreateFieldDef()
  const deleteMutation = useDeleteFieldDef()
  const [deleteTarget, setDeleteTarget] = useState<FieldDef | null>(null)

  const createFieldDef = useCallback(
    async (values: CreateFieldDefFormValues) => {
      await createMutation.mutateAsync({
        entityTypeId,
        fieldKey: values.fieldKey,
        dataType: values.dataType,
      })
    },
    [createMutation, entityTypeId],
  )

  const requestDelete = useCallback((fieldDef: FieldDef) => {
    setDeleteTarget(fieldDef)
  }, [])

  const cancelDelete = useCallback(() => {
    setDeleteTarget(null)
  }, [])

  const confirmDelete = useCallback(async () => {
    if (deleteTarget === null) {
      return
    }

    const id: FieldDefId = deleteTarget.id
    await deleteMutation.mutateAsync({ id, entityTypeId })
    setDeleteTarget(null)
  }, [deleteMutation, deleteTarget, entityTypeId])

  return {
    items: listQuery.data?.items ?? [],
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    errorTitle: listQuery.error?.title ?? null,
    refetch: listQuery.refetch,
    createFieldDef,
    isCreating: createMutation.isPending,
    createErrorTitle: createMutation.error?.title ?? null,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting: deleteMutation.isPending,
  }
}
