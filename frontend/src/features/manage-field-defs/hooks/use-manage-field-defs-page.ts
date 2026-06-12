import { useCallback, useState } from 'react'
import {
  defaultFieldDefListParams,
  useCreateFieldDef,
  useDeleteFieldDef,
  useFieldDefList,
  useUpdateFieldDef,
  type FieldDef,
  type FieldDefId,
} from '@/entities/field-def'
import type { CreateFieldDefFormValues } from './use-create-field-def-form'

export function useManageFieldDefsPage(entityTypeId: number) {
  const listParams = defaultFieldDefListParams(entityTypeId)
  const listQuery = useFieldDefList(listParams)
  const createMutation = useCreateFieldDef()
  const updateMutation = useUpdateFieldDef()
  const deleteMutation = useDeleteFieldDef()
  const [editTarget, setEditTarget] = useState<FieldDef | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<FieldDef | null>(null)

  const createFieldDef = useCallback(
    async (values: CreateFieldDefFormValues) => {
      await createMutation.mutateAsync({
        entityTypeId,
        fieldKey: values.fieldKey,
        dataType: values.dataType,
        region: values.region === 'main' ? null : values.region,
        displayOrder: values.displayOrder,
      })
    },
    [createMutation, entityTypeId],
  )

  const requestEdit = useCallback((fieldDef: FieldDef) => {
    setEditTarget(fieldDef)
  }, [])

  const cancelEdit = useCallback(() => {
    setEditTarget(null)
  }, [])

  const updateFieldDef = useCallback(
    async (values: CreateFieldDefFormValues) => {
      if (editTarget === null) {
        return
      }

      await updateMutation.mutateAsync({
        id: editTarget.id,
        input: {
          entityTypeId,
          fieldKey: values.fieldKey,
          dataType: values.dataType,
          region: values.region === 'main' ? null : values.region,
          displayOrder: values.displayOrder,
        },
      })
      setEditTarget(null)
    },
    [editTarget, entityTypeId, updateMutation],
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
    editTarget,
    requestEdit,
    cancelEdit,
    updateFieldDef,
    isUpdating: updateMutation.isPending,
    updateErrorTitle: updateMutation.error?.title ?? null,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting: deleteMutation.isPending,
  }
}
