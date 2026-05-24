import { useCallback, useMemo } from 'react'
import { defaultFieldDefListParams, useFieldDefList } from '@/entities/field-def'
import { toEntityId, useEntity } from '@/entities/entity'
import {
  useCreateTextField,
  useTextFieldList,
  useUpdateTextField,
  type TextField,
} from '@/entities/text-field'

export function useEditEntityTextFieldsPage(entityTypeId: number, entityId: number) {
  const entityQuery = useEntity(toEntityId(entityId))
  const fieldDefQuery = useFieldDefList(defaultFieldDefListParams(entityTypeId))
  const textFieldQuery = useTextFieldList()
  const createMutation = useCreateTextField()
  const updateMutation = useUpdateTextField()

  const textFieldDefs = useMemo(
    () => (fieldDefQuery.data?.items ?? []).filter((item) => item.dataType === 'text'),
    [fieldDefQuery.data?.items],
  )

  const textFieldsForEntity = useMemo((): TextField[] => {
    return (textFieldQuery.data?.items ?? []).filter((item) => item.entityId === entityId)
  }, [textFieldQuery.data?.items, entityId])

  const initialValues = useMemo((): Record<string, string> => {
    return Object.fromEntries(
      textFieldDefs.map((fieldDef) => {
        const existing = textFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
        return [fieldDef.fieldKey, existing?.value ?? '']
      }),
    )
  }, [textFieldDefs, textFieldsForEntity])

  const saveTextFields = useCallback(
    async (values: Record<string, string>) => {
      for (const fieldDef of textFieldDefs) {
        const value = values[fieldDef.fieldKey] ?? ''
        const existing = textFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)

        if (existing !== undefined) {
          await updateMutation.mutateAsync({
            id: existing.id,
            input: { fieldKey: fieldDef.fieldKey, value },
          })
        } else {
          await createMutation.mutateAsync({
            entityId,
            fieldKey: fieldDef.fieldKey,
            value,
          })
        }
      }
    },
    [createMutation, entityId, textFieldDefs, textFieldsForEntity, updateMutation],
  )

  const isLoading = entityQuery.isLoading || fieldDefQuery.isLoading || textFieldQuery.isLoading
  const isError = entityQuery.isError || fieldDefQuery.isError || textFieldQuery.isError
  const errorTitle =
    entityQuery.error?.title ?? fieldDefQuery.error?.title ?? textFieldQuery.error?.title ?? null

  return {
    entity: entityQuery.data ?? null,
    textFieldDefs,
    initialValues,
    isLoading,
    isError,
    errorTitle,
    refetch: async () => {
      await Promise.all([entityQuery.refetch(), fieldDefQuery.refetch(), textFieldQuery.refetch()])
    },
    saveTextFields,
    isSaving: createMutation.isPending || updateMutation.isPending,
    saveErrorTitle: createMutation.error?.title ?? updateMutation.error?.title ?? null,
  }
}
