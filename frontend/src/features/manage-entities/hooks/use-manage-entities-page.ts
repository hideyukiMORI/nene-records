import { useCallback, useMemo, useState } from 'react'
import {
  defaultEntityListParams,
  useCreateEntity,
  useDeleteEntity,
  useEntityList,
  type Entity,
  type EntityId,
  type EntityRelationFilters,
  type EntityStatus,
} from '@/entities/entity'
import {
  defaultFieldDefListParams,
  isRelationFieldDef,
  useFieldDefList,
  type RelationFieldDef,
} from '@/entities/field-def'
import { useTagList } from '@/entities/tag'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'

export function useManageEntitiesPage(entityTypeId: number) {
  const [selectedTagSlugs, setSelectedTagSlugs] = useState<string[]>([])
  const [selectedRelationFilters, setSelectedRelationFilters] = useState<EntityRelationFilters>({})
  const [searchQuery, setSearchQuery] = useState('')
  const [selectedStatus, setSelectedStatus] = useState<EntityStatus | undefined>(undefined)
  const listParams = useMemo(
    () =>
      defaultEntityListParams(
        entityTypeId,
        selectedTagSlugs,
        selectedRelationFilters,
        0,
        searchQuery,
        selectedStatus,
      ),
    [entityTypeId, selectedRelationFilters, selectedTagSlugs, searchQuery, selectedStatus],
  )
  const listQuery = useEntityList(listParams)
  const tagListQuery = useTagList({ limit: 100, offset: 0 })
  const fieldDefQuery = useFieldDefList(defaultFieldDefListParams(entityTypeId))
  const textFieldQuery = useTextFieldList(defaultTextFieldListParamsForEntityType(entityTypeId))
  const createMutation = useCreateEntity()
  const deleteMutation = useDeleteEntity()
  const [deleteTarget, setDeleteTarget] = useState<Entity | null>(null)

  const relationFieldDefs = useMemo((): RelationFieldDef[] => {
    return (fieldDefQuery.data?.items ?? []).filter(isRelationFieldDef)
  }, [fieldDefQuery.data?.items])

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

  const setRelationFilter = useCallback((fieldKey: string, targetEntityId: number | undefined) => {
    setSelectedRelationFilters((current) => {
      if (targetEntityId === undefined) {
        return Object.fromEntries(Object.entries(current).filter(([key]) => key !== fieldKey))
      }

      return { ...current, [fieldKey]: targetEntityId }
    })
  }, [])

  const clearRelationFilters = useCallback(() => {
    setSelectedRelationFilters({})
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

  const isLoading = listQuery.isLoading || textFieldQuery.isLoading || fieldDefQuery.isLoading
  const isError = listQuery.isError || textFieldQuery.isError || fieldDefQuery.isError
  const errorTitle =
    listQuery.error?.title ?? textFieldQuery.error?.title ?? fieldDefQuery.error?.title ?? null

  return {
    items,
    recordLabels,
    total: listQuery.data?.total ?? 0,
    availableTags: tagListQuery.data?.items ?? [],
    relationFieldDefs,
    selectedTagSlugs,
    selectedRelationFilters,
    selectedStatus,
    setStatus: setSelectedStatus,
    searchQuery,
    setSearchQuery,
    toggleTagSlug,
    clearTagFilter,
    setRelationFilter,
    clearRelationFilters,
    isFilterActive:
      selectedTagSlugs.length > 0 ||
      Object.keys(selectedRelationFilters).length > 0 ||
      searchQuery !== '' ||
      selectedStatus !== undefined,
    isLoading,
    isError,
    errorTitle,
    refetch: async () => {
      await Promise.all([
        listQuery.refetch(),
        textFieldQuery.refetch(),
        tagListQuery.refetch(),
        fieldDefQuery.refetch(),
      ])
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
