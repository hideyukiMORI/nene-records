import { useMemo } from 'react'
import { defaultEntityListParams, useEntityList } from '@/entities/entity'
import type { RelationFieldDef } from '@/entities/field-def'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'

export interface EntityRelationFilterOption {
  id: number
  label: string
}

export function useEntityRelationFilterField(fieldDef: RelationFieldDef) {
  const targetEntityQuery = useEntityList(defaultEntityListParams(fieldDef.targetEntityTypeId))
  const textFieldQuery = useTextFieldList(
    defaultTextFieldListParamsForEntityType(fieldDef.targetEntityTypeId),
  )

  const targetOptions = useMemo((): EntityRelationFilterOption[] => {
    const entities = targetEntityQuery.data?.items ?? []
    const textFields = textFieldQuery.data?.items ?? []

    return entities.map((entity) => ({
      id: Number(entity.id),
      label: getRecordDisplayLabel(Number(entity.id), textFields, `Record #${String(entity.id)}`),
    }))
  }, [targetEntityQuery.data?.items, textFieldQuery.data?.items])

  const isLoading = targetEntityQuery.isLoading || textFieldQuery.isLoading
  const isError = targetEntityQuery.isError || textFieldQuery.isError
  const errorTitle = targetEntityQuery.error?.title ?? textFieldQuery.error?.title ?? null

  return {
    targetOptions,
    isLoading,
    isError,
    errorTitle,
    refetch: async () => {
      await Promise.all([targetEntityQuery.refetch(), textFieldQuery.refetch()])
    },
  }
}
