import { useMemo } from 'react'
import { defaultEntityListParams, useEntityList } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { useTranslation } from '@/shared/i18n'
import { findEntityTypeBySlug } from '@/shared/lib/find-entity-type-by-slug'
import { formatPublishedDate } from '@/shared/lib/format-published-date'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'
import { resolvePermalink } from '@/shared/lib/resolve-permalink'
import { PUBLIC_BROWSE_PAGE_SIZE } from '../lib/public-browse-pagination'

export interface PublicRecordListItem {
  id: number
  label: string
  /** Resolved public URL for this record based on the entity type's permalink pattern */
  publicUrl: string
  /** Human-readable published date (empty when unavailable). */
  publishedLabel: string
}

/** A browsable entity type, for the type-switch chips. */
export interface PublicBrowseType {
  slug: string
  name: string
  href: string
}

export function usePublicBrowseEntityRecordsPage(entityTypeSlug: string, offset: number) {
  const { locale } = useTranslation()
  const entityTypeQuery = useEntityTypeList()
  const entityType = useMemo(
    () => findEntityTypeBySlug(entityTypeQuery.data?.items ?? [], entityTypeSlug),
    [entityTypeQuery.data?.items, entityTypeSlug],
  )

  const entityTypeId = entityType !== undefined ? Number(entityType.id) : 0
  const listParams = useMemo(
    () => ({
      ...defaultEntityListParams(entityTypeId, [], {}, offset),
      status: 'published' as const,
    }),
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
    const pattern = entityType?.permalinkPattern

    return entities.map((entity) => {
      const id = Number(entity.id)
      return {
        id,
        label: getRecordDisplayLabel(id, textFields, `Record #${String(id)}`, locale),
        publicUrl: resolvePermalink(pattern, {
          typeSlug: entityTypeSlug,
          entitySlug: entity.slug ?? null,
          entityId: id,
          publishedAt: entity.publishedAt ?? null,
        }),
        publishedLabel: formatPublishedDate(entity.publishedAt ?? null),
      }
    })
  }, [
    entityListQuery.data?.items,
    textFieldQuery.data?.items,
    entityType?.permalinkPattern,
    entityTypeSlug,
    locale,
  ])

  const entityTypes = useMemo(
    (): PublicBrowseType[] =>
      (entityTypeQuery.data?.items ?? []).map((type) => ({
        slug: type.slug,
        name: type.name,
        href: `/${type.slug}`,
      })),
    [entityTypeQuery.data?.items],
  )

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
    entityTypes,
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
