import { useMemo } from 'react'
import { defaultEntityListParams, useEntityList } from '@/entities/entity'
import { toEntityTypeId, useEntityType } from '@/entities/entity-type'
import type { RelationFieldDef } from '@/entities/field-def'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'

export interface InverseRelationRecordItem {
  id: number
  label: string
}

export function useInverseRelationPanel(fieldDef: RelationFieldDef, targetEntityId: number) {
  const listQuery = useEntityList(
    defaultEntityListParams(fieldDef.entityTypeId, [], {
      [fieldDef.fieldKey]: targetEntityId,
    }),
  )
  const textFieldQuery = useTextFieldList(
    defaultTextFieldListParamsForEntityType(fieldDef.entityTypeId),
  )
  const entityTypeQuery = useEntityType(toEntityTypeId(fieldDef.entityTypeId))

  const items = useMemo((): InverseRelationRecordItem[] => {
    const entities = listQuery.data?.items ?? []
    const textFields = textFieldQuery.data?.items ?? []

    return entities.map((entity) => ({
      id: Number(entity.id),
      label: getRecordDisplayLabel(Number(entity.id), textFields, `Record #${String(entity.id)}`),
    }))
  }, [listQuery.data?.items, textFieldQuery.data?.items])

  const isLoading = listQuery.isLoading || textFieldQuery.isLoading || entityTypeQuery.isLoading
  const isError = listQuery.isError || textFieldQuery.isError || entityTypeQuery.isError
  const errorTitle =
    listQuery.error?.title ?? textFieldQuery.error?.title ?? entityTypeQuery.error?.title ?? null

  return {
    sourceEntityTypeName: entityTypeQuery.data?.name ?? null,
    sourceEntityTypeSlug: entityTypeQuery.data?.slug ?? null,
    items,
    total: listQuery.data?.total ?? 0,
    isLoading,
    isError,
    errorTitle,
    refetch: async () => {
      await Promise.all([listQuery.refetch(), textFieldQuery.refetch(), entityTypeQuery.refetch()])
    },
  }
}
