import { useCallback, useMemo, useState } from 'react'
import {
  defaultEntityListParams,
  useCreateEntity,
  useDeleteEntity,
  useEntityList,
  type Entity,
  type EntityId,
  type EntityRelationFilters,
  type EntitySortKey,
  type EntitySortOrder,
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
import { useTranslation } from '@/shared/i18n'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'

const PAGE_LIMIT = 20

export function useManageEntitiesPage(entityTypeId: number) {
  const { t } = useTranslation()
  const [selectedTagSlugs, setSelectedTagSlugs] = useState<string[]>([])
  const [selectedRelationFilters, setSelectedRelationFilters] = useState<EntityRelationFilters>({})
  const [searchQuery, setSearchQuery] = useState('')
  const [selectedStatus, setSelectedStatus] = useState<EntityStatus | undefined>(undefined)
  const [sortKey, setSortKey] = useState<EntitySortKey>('id')
  const [sortOrder, setSortOrder] = useState<EntitySortOrder>('desc')
  const [page, setPage] = useState(0)
  const listParams = useMemo(
    () =>
      defaultEntityListParams(
        entityTypeId,
        selectedTagSlugs,
        selectedRelationFilters,
        page * PAGE_LIMIT,
        searchQuery,
        selectedStatus,
        sortKey,
        sortOrder,
      ),
    [
      entityTypeId,
      page,
      selectedRelationFilters,
      selectedTagSlugs,
      searchQuery,
      selectedStatus,
      sortKey,
      sortOrder,
    ],
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
        getRecordDisplayLabel(
          Number(entity.id),
          textFields,
          t('admin.entityRecord.id', { id: entity.id }),
        ),
      ]),
    )
  }, [items, t, textFieldQuery.data?.items])

  const toggleTagSlug = useCallback((slug: string) => {
    setSelectedTagSlugs((current) =>
      current.includes(slug) ? current.filter((item) => item !== slug) : [...current, slug],
    )
    setPage(0)
  }, [])

  const clearTagFilter = useCallback(() => {
    setSelectedTagSlugs([])
    setPage(0)
  }, [])

  const setRelationFilter = useCallback((fieldKey: string, targetEntityId: number | undefined) => {
    setSelectedRelationFilters((current) => {
      if (targetEntityId === undefined) {
        return Object.fromEntries(Object.entries(current).filter(([key]) => key !== fieldKey))
      }

      return { ...current, [fieldKey]: targetEntityId }
    })
    setPage(0)
  }, [])

  const clearRelationFilters = useCallback(() => {
    setSelectedRelationFilters({})
    setPage(0)
  }, [])

  const setStatus = useCallback((status: EntityStatus | undefined) => {
    setSelectedStatus(status)
    setPage(0)
  }, [])

  const setSearch = useCallback((q: string) => {
    setSearchQuery(q)
    setPage(0)
  }, [])

  const setSort = useCallback((key: EntitySortKey, order: EntitySortOrder) => {
    setSortKey(key)
    setSortOrder(order)
    setPage(0)
  }, [])

  const createEntity = useCallback(async (): Promise<Entity> => {
    return createMutation.mutateAsync({ entityTypeId })
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

  const total = listQuery.data?.total ?? 0
  const totalPages = Math.max(1, Math.ceil(total / PAGE_LIMIT))

  return {
    items,
    recordLabels,
    total,
    page,
    totalPages,
    sortKey,
    sortOrder,
    setSort,
    prevPage:
      page > 0
        ? () => {
            setPage((p) => p - 1)
          }
        : undefined,
    nextPage:
      page < totalPages - 1
        ? () => {
            setPage((p) => p + 1)
          }
        : undefined,
    availableTags: tagListQuery.data?.items ?? [],
    relationFieldDefs,
    selectedTagSlugs,
    selectedRelationFilters,
    selectedStatus,
    setStatus,
    searchQuery,
    setSearchQuery: setSearch,
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
