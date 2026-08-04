import { useMemo } from 'react'
import { defaultEntityListParams, useEntityList } from '@/entities/entity'
import { useEntityRelationList } from '@/entities/entity-relation'
import type { RelationFieldDef } from '@/entities/field-def'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { useTranslation } from '@/shared/i18n'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'
import { resolvePermalink } from '@/shared/lib/resolve-permalink'

export interface PublicRelationTargetLink {
  targetEntityId: number
  label: string
  href: string
}

export function usePublicRelationFieldDisplay(
  entityId: number,
  fieldDef: RelationFieldDef,
  entityTypeSlugById: Record<number, string>,
  entityTypePatternById: Record<number, string | null | undefined>,
) {
  const { locale } = useTranslation()
  const relationQuery = useEntityRelationList(entityId, fieldDef.fieldKey)

  // Load text fields for label generation
  const textFieldQuery = useTextFieldList(
    defaultTextFieldListParamsForEntityType(fieldDef.targetEntityTypeId),
  )

  // Load entity list for the target type to get slug/publishedAt for permalink resolution.
  // limit:200 mirrors the text-field ceiling; relations are typically small in practice.
  const targetEntityListParams = useMemo(
    () => ({
      ...defaultEntityListParams(fieldDef.targetEntityTypeId),
      limit: 200,
    }),
    [fieldDef.targetEntityTypeId],
  )
  const targetEntityListQuery = useEntityList(targetEntityListParams)

  const targets = useMemo((): PublicRelationTargetLink[] => {
    const relations = relationQuery.data?.items ?? []
    const textFields = textFieldQuery.data?.items ?? []
    const targetEntities = targetEntityListQuery.data?.items ?? []
    const targetSlug =
      entityTypeSlugById[fieldDef.targetEntityTypeId] ?? String(fieldDef.targetEntityTypeId)
    const targetPattern = entityTypePatternById[fieldDef.targetEntityTypeId]

    return relations.map((relation) => {
      const targetEntity = targetEntities.find((e) => Number(e.id) === relation.targetEntityId)
      return {
        targetEntityId: relation.targetEntityId,
        label: getRecordDisplayLabel(
          relation.targetEntityId,
          textFields,
          `Record #${String(relation.targetEntityId)}`,
          locale,
        ),
        href: resolvePermalink(targetPattern, {
          typeSlug: targetSlug,
          entitySlug: targetEntity?.slug ?? null,
          entityId: relation.targetEntityId,
          publishedAt: targetEntity?.publishedAt ?? null,
        }),
      }
    })
  }, [
    entityTypePatternById,
    entityTypeSlugById,
    fieldDef.targetEntityTypeId,
    relationQuery.data?.items,
    targetEntityListQuery.data?.items,
    textFieldQuery.data?.items,
    locale,
  ])

  const isLoading =
    relationQuery.isLoading || textFieldQuery.isLoading || targetEntityListQuery.isLoading
  const isError = relationQuery.isError || textFieldQuery.isError || targetEntityListQuery.isError
  const errorTitle =
    relationQuery.error?.title ??
    textFieldQuery.error?.title ??
    targetEntityListQuery.error?.title ??
    null

  return {
    targets,
    isLoading,
    isError,
    errorTitle,
    refetch: async () => {
      await Promise.all([
        relationQuery.refetch(),
        textFieldQuery.refetch(),
        targetEntityListQuery.refetch(),
      ])
    },
  }
}
