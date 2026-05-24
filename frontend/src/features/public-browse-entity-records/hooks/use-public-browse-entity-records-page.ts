import { useMemo } from 'react'
import { defaultEntityListParams, useEntityList } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { findEntityTypeBySlug } from '@/shared/lib/find-entity-type-by-slug'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'
import { PUBLIC_BROWSE_PAGE_SIZE } from '../lib/public-browse-pagination'

export interface PublicRecordListItem {
  id: number
  label: string
}

export function usePublicBrowseEntityRecordsPage(entityTypeSlug: string, offset: number) {
  const entityTypeQuery = useEntityTypeList()
  const entityType = useMemo(
    () => findEntityTypeBySlug(entityTypeQuery.data?.items ?? [], entityTypeSlug),
    [entityTypeQuery.data?.items, entityTypeSlug],
  )

  const entityTypeId = entityType !== undefined ? Number(entityType.id) : 0
  const listParams = useMemo(
    () => defaultEntityListParams(entityTypeId, [], {}, offset),
    [entityTypeId, offset],
  )
  const entityListQuery = useEntityList(listParams, { enabled: entityTypeId > 0 })
  const textFieldListParams = useMemo(
    () => defaultTextFieldListParamsForEntityType(entityTypeId),
    [entityTypeId],
  )
  const textFieldQuery = useTextFieldList(textFieldListParams, { enabled: entityTypeId > 0 })

  const items = useMemo((): PublicRecordListItem[] => {
    const entities = entityListQuery.data?.items ?? []
    const textFields = textFieldQuery.data?.items ?? []

    return entities.map((entity) => ({
      id: Number(entity.id),
      label: getRecordDisplayLabel(Number(entity.id), textFields, `Record #${String(entity.id)}`),
    }))
  }, [entityListQuery.data?.items, textFieldQuery.data?.items])

  const isLoading =
    entityTypeQuery.isLoading ||
    (entityTypeId > 0 && entityListQuery.isLoading) ||
    (entityTypeId > 0 && textFieldQuery.isLoading)
  const isError = entityTypeQuery.isError || entityListQuery.isError || textFieldQuery.isError
  const errorTitle =
    entityTypeQuery.error?.title ??
    entityListQuery.error?.title ??
    textFieldQuery.error?.title ??
    null

  const total = entityListQuery.data?.total ?? 0
  const pageSize = PUBLIC_BROWSE_PAGE_SIZE
  const hasPreviousPage = offset > 0
  const hasNextPage = offset + pageSize < total

  return {
    entityType,
    entityTypeSlug,
    items,
    total,
    offset,
    pageSize,
    hasPreviousPage,
    hasNextPage,
    isLoading,
    isError,
    errorTitle,
    isUnknownType: !entityTypeQuery.isLoading && entityType === undefined,
    refetch: async () => {
      await Promise.all([
        entityTypeQuery.refetch(),
        entityListQuery.refetch(),
        textFieldQuery.refetch(),
      ])
    },
  }
}
