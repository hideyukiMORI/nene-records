import { useMemo } from 'react'
import { defaultEntityListParams, useEntityList } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { findEntityTypeBySlug } from '@/shared/lib/find-entity-type-by-slug'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'
import { resolvePermalink } from '@/shared/lib/resolve-permalink'

export interface RecentPostItem {
  id: number
  label: string
  publicUrl: string
}

/** Recent published records of a type, newest first, resolved to label + URL. */
export function useRecentPosts(entityTypeSlug: string, limit: number) {
  const entityTypeQuery = useEntityTypeList()
  const entityType = useMemo(
    () => findEntityTypeBySlug(entityTypeQuery.data?.items ?? [], entityTypeSlug),
    [entityTypeQuery.data?.items, entityTypeSlug],
  )
  const entityTypeId = entityType !== undefined ? Number(entityType.id) : 0

  const listParams = useMemo(
    () =>
      defaultEntityListParams(
        entityTypeId,
        [],
        {},
        0,
        undefined,
        'published',
        'published_at',
        'desc',
      ),
    [entityTypeId],
  )
  const entityListQuery = useEntityList(listParams, { enabled: entityTypeId > 0 })
  const textFieldQuery = useTextFieldList(defaultTextFieldListParamsForEntityType(entityTypeId), {
    enabled: entityTypeId > 0,
  })

  const items = useMemo((): RecentPostItem[] => {
    const entities = entityListQuery.data?.items ?? []
    const textFields = textFieldQuery.data?.items ?? []
    const pattern = entityType?.permalinkPattern

    return entities.slice(0, Math.max(0, limit)).map((entity) => {
      const id = Number(entity.id)
      return {
        id,
        label: getRecordDisplayLabel(id, textFields, `Record #${String(id)}`),
        publicUrl: resolvePermalink(pattern, {
          typeSlug: entityTypeSlug,
          entitySlug: entity.slug ?? null,
          entityId: id,
          publishedAt: entity.publishedAt ?? null,
        }),
      }
    })
  }, [
    entityListQuery.data?.items,
    textFieldQuery.data?.items,
    entityType?.permalinkPattern,
    entityTypeSlug,
    limit,
  ])

  return {
    items,
    isLoading: entityTypeQuery.isLoading || (entityTypeId > 0 && entityListQuery.isLoading),
  }
}
