import { useCallback, useMemo, useState } from 'react'
import {
  useAttachEntityRelation,
  useDetachEntityRelation,
  useEntityRelationList,
  type EntityRelation,
} from '@/entities/entity-relation'
import { defaultEntityListParams, useEntityList } from '@/entities/entity'
import type { RelationFieldDef } from '@/entities/field-def'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'

export function useRelationFieldPanel(entityId: number, fieldDef: RelationFieldDef) {
  const relationQuery = useEntityRelationList(entityId, fieldDef.fieldKey)
  const targetEntityQuery = useEntityList(defaultEntityListParams(fieldDef.targetEntityTypeId))
  const textFieldQuery = useTextFieldList(
    defaultTextFieldListParamsForEntityType(fieldDef.targetEntityTypeId),
  )
  const attachMutation = useAttachEntityRelation()
  const detachMutation = useDetachEntityRelation()
  const [selectedTargetId, setSelectedTargetId] = useState('')

  const attachedRelations = useMemo(
    () => relationQuery.data?.items ?? [],
    [relationQuery.data?.items],
  )

  const targetLabels = useMemo((): Record<string, string> => {
    const entities = targetEntityQuery.data?.items ?? []
    const textFields = textFieldQuery.data?.items ?? []

    return Object.fromEntries(
      entities.map((entity) => [
        String(entity.id),
        getRecordDisplayLabel(Number(entity.id), textFields, `Record #${String(entity.id)}`),
      ]),
    )
  }, [targetEntityQuery.data?.items, textFieldQuery.data?.items])

  const attachedTargetIds = useMemo(
    () => new Set(attachedRelations.map((relation) => relation.targetEntityId)),
    [attachedRelations],
  )

  const availableTargetIds = useMemo((): number[] => {
    const entities = targetEntityQuery.data?.items ?? []

    return entities.map((entity) => Number(entity.id)).filter((id) => !attachedTargetIds.has(id))
  }, [attachedTargetIds, targetEntityQuery.data?.items])

  const attachTarget = useCallback(async () => {
    if (selectedTargetId === '') {
      return
    }

    await attachMutation.mutateAsync({
      entityId,
      fieldKey: fieldDef.fieldKey,
      targetEntityId: Number(selectedTargetId),
    })
    setSelectedTargetId('')
  }, [attachMutation, entityId, fieldDef.fieldKey, selectedTargetId])

  const detachTarget = useCallback(
    async (relation: EntityRelation) => {
      await detachMutation.mutateAsync({
        entityId,
        fieldKey: fieldDef.fieldKey,
        targetEntityId: relation.targetEntityId,
      })
    },
    [detachMutation, entityId, fieldDef.fieldKey],
  )

  const isLoading =
    relationQuery.isLoading || targetEntityQuery.isLoading || textFieldQuery.isLoading
  const isError = relationQuery.isError || targetEntityQuery.isError || textFieldQuery.isError
  const errorTitle =
    relationQuery.error?.title ??
    targetEntityQuery.error?.title ??
    textFieldQuery.error?.title ??
    null

  return {
    attachedRelations,
    targetLabels,
    availableTargetIds,
    selectedTargetId,
    setSelectedTargetId,
    isLoading,
    isError,
    errorTitle,
    isAttaching: attachMutation.isPending,
    attachErrorTitle: attachMutation.error?.title ?? null,
    isDetaching: detachMutation.isPending,
    attachTarget,
    detachTarget,
    refetch: async () => {
      await Promise.all([
        relationQuery.refetch(),
        targetEntityQuery.refetch(),
        textFieldQuery.refetch(),
      ])
    },
  }
}
