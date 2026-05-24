import { useCallback, useMemo } from 'react'
import type { FieldDataType } from '@/entities/field-def'
import { defaultFieldDefListParams, useFieldDefList } from '@/entities/field-def'
import { toEntityId, useEntity } from '@/entities/entity'
import {
  useCreateIntField,
  useIntFieldList,
  useUpdateIntField,
  type IntField,
} from '@/entities/int-field'
import {
  useCreateTextField,
  useTextFieldList,
  useUpdateTextField,
  type TextField,
} from '@/entities/text-field'

const EDITABLE_DATA_TYPES: FieldDataType[] = ['text', 'int']

function parseIntFieldValue(raw: string): number {
  const trimmed = raw.trim()
  if (trimmed === '') {
    return 0
  }

  const parsed = Number.parseInt(trimmed, 10)
  return Number.isNaN(parsed) ? 0 : parsed
}

export function useEditEntityTextFieldsPage(entityTypeId: number, entityId: number) {
  const entityQuery = useEntity(toEntityId(entityId))
  const fieldDefQuery = useFieldDefList(defaultFieldDefListParams(entityTypeId))
  const textFieldQuery = useTextFieldList()
  const intFieldQuery = useIntFieldList()
  const createTextMutation = useCreateTextField()
  const updateTextMutation = useUpdateTextField()
  const createIntMutation = useCreateIntField()
  const updateIntMutation = useUpdateIntField()

  const editableFieldDefs = useMemo(
    () =>
      (fieldDefQuery.data?.items ?? []).filter((item) =>
        EDITABLE_DATA_TYPES.includes(item.dataType),
      ),
    [fieldDefQuery.data?.items],
  )

  const textFieldsForEntity = useMemo((): TextField[] => {
    return (textFieldQuery.data?.items ?? []).filter((item) => item.entityId === entityId)
  }, [textFieldQuery.data?.items, entityId])

  const intFieldsForEntity = useMemo((): IntField[] => {
    return (intFieldQuery.data?.items ?? []).filter((item) => item.entityId === entityId)
  }, [intFieldQuery.data?.items, entityId])

  const initialValues = useMemo((): Record<string, string> => {
    return Object.fromEntries(
      editableFieldDefs.map((fieldDef) => {
        if (fieldDef.dataType === 'text') {
          const existing = textFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
          return [fieldDef.fieldKey, existing?.value ?? '']
        }

        const existing = intFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
        return [fieldDef.fieldKey, existing !== undefined ? String(existing.value) : '']
      }),
    )
  }, [editableFieldDefs, intFieldsForEntity, textFieldsForEntity])

  const saveTextFields = useCallback(
    async (values: Record<string, string>) => {
      for (const fieldDef of editableFieldDefs) {
        const rawValue = values[fieldDef.fieldKey] ?? ''

        if (fieldDef.dataType === 'text') {
          const existing = textFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)

          if (existing !== undefined) {
            await updateTextMutation.mutateAsync({
              id: existing.id,
              input: { fieldKey: fieldDef.fieldKey, value: rawValue },
            })
          } else {
            await createTextMutation.mutateAsync({
              entityId,
              fieldKey: fieldDef.fieldKey,
              value: rawValue,
            })
          }

          continue
        }

        const intValue = parseIntFieldValue(rawValue)
        const existing = intFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)

        if (existing !== undefined) {
          await updateIntMutation.mutateAsync({
            id: existing.id,
            input: { fieldKey: fieldDef.fieldKey, value: intValue },
          })
        } else {
          await createIntMutation.mutateAsync({
            entityId,
            fieldKey: fieldDef.fieldKey,
            value: intValue,
          })
        }
      }
    },
    [
      createIntMutation,
      createTextMutation,
      editableFieldDefs,
      entityId,
      intFieldsForEntity,
      textFieldsForEntity,
      updateIntMutation,
      updateTextMutation,
    ],
  )

  const isLoading =
    entityQuery.isLoading ||
    fieldDefQuery.isLoading ||
    textFieldQuery.isLoading ||
    intFieldQuery.isLoading
  const isError =
    entityQuery.isError || fieldDefQuery.isError || textFieldQuery.isError || intFieldQuery.isError
  const errorTitle =
    entityQuery.error?.title ??
    fieldDefQuery.error?.title ??
    textFieldQuery.error?.title ??
    intFieldQuery.error?.title ??
    null

  return {
    entity: entityQuery.data ?? null,
    textFieldDefs: editableFieldDefs,
    initialValues,
    isLoading,
    isError,
    errorTitle,
    refetch: async () => {
      await Promise.all([
        entityQuery.refetch(),
        fieldDefQuery.refetch(),
        textFieldQuery.refetch(),
        intFieldQuery.refetch(),
      ])
    },
    saveTextFields,
    isSaving:
      createTextMutation.isPending ||
      updateTextMutation.isPending ||
      createIntMutation.isPending ||
      updateIntMutation.isPending,
    saveErrorTitle:
      createTextMutation.error?.title ??
      updateTextMutation.error?.title ??
      createIntMutation.error?.title ??
      updateIntMutation.error?.title ??
      null,
  }
}
