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
import { useCreateFieldDef, type FieldDataType } from '@/entities/field-def'
import {
  formValuesToLabels,
  type CreateEntityTypeFormValues,
  type EditEntityTypeFormValues,
  type EntityTypeStarter,
} from './use-create-entity-type-form'

/** Fields auto-provisioned per starter when a content type is created (#491 WS1). */
const STARTER_FIELDS: Record<
  EntityTypeStarter,
  readonly { fieldKey: string; dataType: FieldDataType }[]
> = {
  blank: [],
  article: [
    { fieldKey: 'title', dataType: 'text' },
    { fieldKey: 'body', dataType: 'markdown' },
  ],
  rich_page: [
    { fieldKey: 'title', dataType: 'text' },
    { fieldKey: 'content', dataType: 'blocks' },
  ],
}

export function useManageEntityTypesPage() {
  const listQuery = useEntityTypeList()
  const createMutation = useCreateEntityType()
  const createFieldDefMutation = useCreateFieldDef()
  const updateMutation = useUpdateEntityType()
  const deleteMutation = useDeleteEntityType()
  const reorderMutation = useReorderEntityTypes()
  const [editTarget, setEditTarget] = useState<EntityType | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<EntityType | null>(null)

  const createEntityType = useCallback(
    async (values: CreateEntityTypeFormValues) => {
      const { starter, ...typeInput } = values
      const created = await createMutation.mutateAsync(typeInput)
      // Provision the starter's fields (e.g. rich_page → title + blocks body).
      for (const [index, field] of STARTER_FIELDS[starter].entries()) {
        await createFieldDefMutation.mutateAsync({
          entityTypeId: created.id,
          fieldKey: field.fieldKey,
          dataType: field.dataType,
          region: null,
          displayOrder: index,
        })
      }
    },
    [createMutation, createFieldDefMutation],
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
    isCreating: createMutation.isPending || createFieldDefMutation.isPending,
    createErrorTitle: createMutation.error?.title ?? createFieldDefMutation.error?.title ?? null,
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
