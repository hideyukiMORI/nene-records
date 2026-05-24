import { useMemo } from 'react'
import { useEntityRelationList } from '@/entities/entity-relation'
import type { RelationFieldDef } from '@/entities/field-def'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'

export interface PublicRelationTargetLink {
  targetEntityId: number
  label: string
  href: string
}

export function usePublicRelationFieldDisplay(
  entityId: number,
  fieldDef: RelationFieldDef,
  entityTypeSlugById: Record<number, string>,
) {
  const relationQuery = useEntityRelationList(entityId, fieldDef.fieldKey)
  const textFieldQuery = useTextFieldList(
    defaultTextFieldListParamsForEntityType(fieldDef.targetEntityTypeId),
  )

  const targets = useMemo((): PublicRelationTargetLink[] => {
    const relations = relationQuery.data?.items ?? []
    const textFields = textFieldQuery.data?.items ?? []
    const targetSlug =
      entityTypeSlugById[fieldDef.targetEntityTypeId] ?? String(fieldDef.targetEntityTypeId)

    return relations.map((relation) => ({
      targetEntityId: relation.targetEntityId,
      label: getRecordDisplayLabel(
        relation.targetEntityId,
        textFields,
        `Record #${String(relation.targetEntityId)}`,
      ),
      href: `/view/${targetSlug}/${String(relation.targetEntityId)}`,
    }))
  }, [
    entityTypeSlugById,
    fieldDef.targetEntityTypeId,
    relationQuery.data?.items,
    textFieldQuery.data?.items,
  ])

  const isLoading = relationQuery.isLoading || textFieldQuery.isLoading
  const isError = relationQuery.isError || textFieldQuery.isError
  const errorTitle = relationQuery.error?.title ?? textFieldQuery.error?.title ?? null

  return {
    targets,
    isLoading,
    isError,
    errorTitle,
    refetch: async () => {
      await Promise.all([relationQuery.refetch(), textFieldQuery.refetch()])
    },
  }
}
