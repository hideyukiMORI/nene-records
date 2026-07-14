import { useCallback, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import {
  defaultEntityListParams,
  useCreateEntity,
  useDeleteEntity,
  useDirectoryEntityList,
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
import { type DirectoryRecord } from '../lib/build-permalink-tree'

export const PAGE_SIZE_OPTIONS = [20, 50, 100] as const
const DEFAULT_PAGE_SIZE = 20

export function useManageEntitiesPage(entityTypeId: number) {
  const { t } = useTranslation()
  // The search query lives in the URL (?q=) so searches are bookmarkable / shareable.
  const [searchParams, setSearchParams] = useSearchParams()
  const searchQuery = searchParams.get('q') ?? ''
  const [selectedTagSlugs, setSelectedTagSlugs] = useState<string[]>([])
  const [selectedRelationFilters, setSelectedRelationFilters] = useState<EntityRelationFilters>({})
  const [selectedStatus, setSelectedStatus] = useState<EntityStatus | undefined>(undefined)
  const [sortKey, setSortKey] = useState<EntitySortKey>('id')
  const [sortOrder, setSortOrder] = useState<EntitySortOrder>('desc')
  const [page, setPage] = useState(0)
  const [pageSize, setPageSizeState] = useState<number>(DEFAULT_PAGE_SIZE)
  const [viewMode, setViewMode] = useState<'list' | 'directory'>('list')
  const listParams = useMemo(
    () => ({
      ...defaultEntityListParams(
        entityTypeId,
        selectedTagSlugs,
        selectedRelationFilters,
        page * pageSize,
        searchQuery,
        selectedStatus,
        sortKey,
        sortOrder,
      ),
      limit: pageSize,
    }),
    [
      entityTypeId,
      page,
      pageSize,
      selectedRelationFilters,
      selectedTagSlugs,
      searchQuery,
      selectedStatus,
      sortKey,
      sortOrder,
    ],
  )
  const listQuery = useEntityList(listParams)
  // Directory mode pages through ALL permalink records (100 at a time, the public
  // endpoint's per-request cap) to build a COMPLETE path tree — honouring the active
  // filters (search / status / tags / relations) (#657). The hook adds has_permalink
  // + include=views; sort/pagination don't apply (the tree sorts by path) (#682).
  const directoryParams = useMemo(
    () =>
      defaultEntityListParams(
        entityTypeId,
        selectedTagSlugs,
        selectedRelationFilters,
        0,
        searchQuery,
        selectedStatus,
        sortKey,
        sortOrder,
      ),
    [
      entityTypeId,
      selectedTagSlugs,
      selectedRelationFilters,
      searchQuery,
      selectedStatus,
      sortKey,
      sortOrder,
    ],
  )
  const directoryQuery = useDirectoryEntityList(directoryParams, {
    enabled: viewMode === 'directory',
  })
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
          null,
          entity.metaTitle,
        ),
      ]),
    )
  }, [items, t, textFieldQuery.data?.items])

  const recordBodyMap = useMemo((): Record<string, string> => {
    const textFields = textFieldQuery.data?.items ?? []
    const result: Record<string, string> = {}
    for (const entity of items) {
      const bodyField = textFields.find(
        (f) => f.entityId === Number(entity.id) && f.fieldKey === 'body',
      )
      if (bodyField !== undefined && bodyField.value.trim() !== '') {
        // One truncated preview line in the UI — never mount a huge body verbatim (#849).
        result[String(entity.id)] = bodyField.value.trim().slice(0, 200)
      }
    }
    return result
  }, [items, textFieldQuery.data?.items])

  // Directory-mode records: only those carrying a custom permalink, labelled from
  // the type-wide title fields (falling back to meta_title or the last segment).
  const directoryRecords = useMemo((): DirectoryRecord[] => {
    const textFields = textFieldQuery.data?.items ?? []
    return (directoryQuery.data?.items ?? [])
      .filter((entity) => entity.permalink !== null && entity.permalink !== '')
      .map((entity) => {
        const permalink = entity.permalink ?? ''
        const lastSegment = permalink.split('/').filter(Boolean).pop() ?? String(entity.id)
        const fallback =
          entity.metaTitle !== null && entity.metaTitle.trim() !== ''
            ? entity.metaTitle
            : lastSegment
        return {
          id: Number(entity.id),
          permalink,
          label: getRecordDisplayLabel(Number(entity.id), textFields, fallback),
          status: entity.status,
          updatedAt: entity.updatedAt,
          menuOrder: entity.menuOrder,
          viewCount: entity.viewCount ?? 0,
        }
      })
  }, [directoryQuery.data?.items, textFieldQuery.data?.items])

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

  const setSearch = useCallback(
    (q: string) => {
      setPage(0)
      setSearchParams(
        (prev) => {
          const next = new URLSearchParams(prev)
          if (q === '') {
            next.delete('q')
          } else {
            next.set('q', q)
          }
          return next
        },
        { replace: true },
      )
    },
    [setSearchParams],
  )

  const setSort = useCallback((key: EntitySortKey, order: EntitySortOrder) => {
    setSortKey(key)
    setSortOrder(order)
    setPage(0)
  }, [])

  const setPageSize = useCallback((size: number) => {
    setPageSizeState(size)
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
  const totalPages = Math.max(1, Math.ceil(total / pageSize))
  const directoryTruncated = directoryQuery.data?.truncated ?? false

  return {
    items,
    recordLabels,
    recordBodyMap,
    total,
    page,
    totalPages,
    pageSize,
    setPageSize,
    pageSizeOptions: PAGE_SIZE_OPTIONS,
    viewMode,
    setViewMode,
    directoryRecords,
    directoryTruncated,
    directoryIsLoading: directoryQuery.isLoading,
    directoryIsError: directoryQuery.isError,
    directoryErrorTitle: directoryQuery.error?.title ?? null,
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
        directoryQuery.refetch(),
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
