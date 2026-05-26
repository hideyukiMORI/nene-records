import { useCallback, useMemo } from 'react'
import { FIELD_DATA_TYPES, type FieldDataType } from '@/entities/field-def'
import { defaultFieldDefListParams, useFieldDefList } from '@/entities/field-def'
import { toEntityId, useEntity } from '@/entities/entity'
import {
  useCreateBoolField,
  useBoolFieldList,
  useUpdateBoolField,
  type BoolField,
} from '@/entities/bool-field'
import {
  useCreateDateTimeField,
  useDateTimeFieldList,
  useUpdateDateTimeField,
  type DateTimeField,
} from '@/entities/datetime-field'
import {
  useCreateEnumField,
  useEnumFieldList,
  useUpdateEnumField,
  type EnumField,
} from '@/entities/enum-field'
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

const EDITABLE_DATA_TYPES: FieldDataType[] = FIELD_DATA_TYPES.filter(
  (dataType) => dataType !== 'relation',
)

const FIELD_LIST_PARAMS = { limit: 100, offset: 0 } as const

function parseIntFieldValue(raw: string): number {
  const trimmed = raw.trim()
  if (trimmed === '') {
    return 0
  }

  const parsed = Number.parseInt(trimmed, 10)
  return Number.isNaN(parsed) ? 0 : parsed
}

function parseBoolFieldValue(raw: string): boolean {
  return raw === 'true'
}

function isoToDatetimeLocal(iso: string): string {
  if (iso.trim() === '') {
    return ''
  }

  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return ''
  }

  const pad = (value: number): string => String(value).padStart(2, '0')

  return `${String(date.getFullYear())}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function datetimeLocalToIso(local: string): string {
  if (local.trim() === '') {
    return ''
  }

  const date = new Date(local)
  if (Number.isNaN(date.getTime())) {
    return ''
  }

  return date.toISOString()
}

export function useEditEntityTextFieldsPage(entityTypeId: number, entityId: number) {
  const listParams = useMemo(() => ({ ...FIELD_LIST_PARAMS, entityId }), [entityId])

  const entityQuery = useEntity(toEntityId(entityId))
  const fieldDefQuery = useFieldDefList(defaultFieldDefListParams(entityTypeId))
  const textFieldQuery = useTextFieldList(listParams)
  const intFieldQuery = useIntFieldList(listParams)
  const enumFieldQuery = useEnumFieldList(listParams)
  const boolFieldQuery = useBoolFieldList(listParams)
  const dateTimeFieldQuery = useDateTimeFieldList(listParams)
  const createTextMutation = useCreateTextField()
  const updateTextMutation = useUpdateTextField()
  const createIntMutation = useCreateIntField()
  const updateIntMutation = useUpdateIntField()
  const createEnumMutation = useCreateEnumField()
  const updateEnumMutation = useUpdateEnumField()
  const createBoolMutation = useCreateBoolField()
  const updateBoolMutation = useUpdateBoolField()
  const createDateTimeMutation = useCreateDateTimeField()
  const updateDateTimeMutation = useUpdateDateTimeField()

  const editableFieldDefs = useMemo(
    () =>
      (fieldDefQuery.data?.items ?? []).filter((item) =>
        EDITABLE_DATA_TYPES.includes(item.dataType),
      ),
    [fieldDefQuery.data?.items],
  )

  const textFieldsForEntity = useMemo((): TextField[] => {
    return textFieldQuery.data?.items ?? []
  }, [textFieldQuery.data?.items])

  const intFieldsForEntity = useMemo((): IntField[] => {
    return intFieldQuery.data?.items ?? []
  }, [intFieldQuery.data?.items])

  const enumFieldsForEntity = useMemo((): EnumField[] => {
    return enumFieldQuery.data?.items ?? []
  }, [enumFieldQuery.data?.items])

  const boolFieldsForEntity = useMemo((): BoolField[] => {
    return boolFieldQuery.data?.items ?? []
  }, [boolFieldQuery.data?.items])

  const dateTimeFieldsForEntity = useMemo((): DateTimeField[] => {
    return dateTimeFieldQuery.data?.items ?? []
  }, [dateTimeFieldQuery.data?.items])

  const initialValues = useMemo((): Record<string, string> => {
    return Object.fromEntries(
      editableFieldDefs.map((fieldDef) => {
        switch (fieldDef.dataType) {
          case 'text':
          case 'image': {
            const existing = textFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
            return [fieldDef.fieldKey, existing?.value ?? '']
          }
          case 'int': {
            const existing = intFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
            return [fieldDef.fieldKey, existing !== undefined ? String(existing.value) : '']
          }
          case 'enum': {
            const existing = enumFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
            return [fieldDef.fieldKey, existing?.value ?? '']
          }
          case 'bool': {
            const existing = boolFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
            return [fieldDef.fieldKey, existing?.value === true ? 'true' : 'false']
          }
          case 'datetime': {
            const existing = dateTimeFieldsForEntity.find(
              (item) => item.fieldKey === fieldDef.fieldKey,
            )
            return [
              fieldDef.fieldKey,
              existing !== undefined ? isoToDatetimeLocal(existing.value) : '',
            ]
          }
          default:
            return [fieldDef.fieldKey, '']
        }
      }),
    )
  }, [
    boolFieldsForEntity,
    dateTimeFieldsForEntity,
    editableFieldDefs,
    enumFieldsForEntity,
    intFieldsForEntity,
    textFieldsForEntity,
  ])

  const saveTextFields = useCallback(
    async (values: Record<string, string>) => {
      for (const fieldDef of editableFieldDefs) {
        const rawValue = values[fieldDef.fieldKey] ?? ''

        switch (fieldDef.dataType) {
          case 'text':
          case 'image': {
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
            break
          }
          case 'int': {
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
            break
          }
          case 'enum': {
            const existing = enumFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)

            if (existing !== undefined) {
              await updateEnumMutation.mutateAsync({
                id: existing.id,
                input: { fieldKey: fieldDef.fieldKey, value: rawValue },
              })
            } else {
              await createEnumMutation.mutateAsync({
                entityId,
                fieldKey: fieldDef.fieldKey,
                value: rawValue,
              })
            }
            break
          }
          case 'bool': {
            const boolValue = parseBoolFieldValue(rawValue)
            const existing = boolFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)

            if (existing !== undefined) {
              await updateBoolMutation.mutateAsync({
                id: existing.id,
                input: { fieldKey: fieldDef.fieldKey, value: boolValue },
              })
            } else {
              await createBoolMutation.mutateAsync({
                entityId,
                fieldKey: fieldDef.fieldKey,
                value: boolValue,
              })
            }
            break
          }
          case 'datetime': {
            const isoValue = datetimeLocalToIso(rawValue)
            const existing = dateTimeFieldsForEntity.find(
              (item) => item.fieldKey === fieldDef.fieldKey,
            )

            if (existing !== undefined) {
              await updateDateTimeMutation.mutateAsync({
                id: existing.id,
                input: { fieldKey: fieldDef.fieldKey, value: isoValue },
              })
            } else {
              await createDateTimeMutation.mutateAsync({
                entityId,
                fieldKey: fieldDef.fieldKey,
                value: isoValue,
              })
            }
            break
          }
        }
      }
    },
    [
      boolFieldsForEntity,
      createBoolMutation,
      createDateTimeMutation,
      createEnumMutation,
      createIntMutation,
      createTextMutation,
      dateTimeFieldsForEntity,
      editableFieldDefs,
      entityId,
      enumFieldsForEntity,
      intFieldsForEntity,
      textFieldsForEntity,
      updateBoolMutation,
      updateDateTimeMutation,
      updateEnumMutation,
      updateIntMutation,
      updateTextMutation,
    ],
  )

  const isLoading =
    entityQuery.isLoading ||
    fieldDefQuery.isLoading ||
    textFieldQuery.isLoading ||
    intFieldQuery.isLoading ||
    enumFieldQuery.isLoading ||
    boolFieldQuery.isLoading ||
    dateTimeFieldQuery.isLoading
  const isError =
    entityQuery.isError ||
    fieldDefQuery.isError ||
    textFieldQuery.isError ||
    intFieldQuery.isError ||
    enumFieldQuery.isError ||
    boolFieldQuery.isError ||
    dateTimeFieldQuery.isError
  const errorTitle =
    entityQuery.error?.title ??
    fieldDefQuery.error?.title ??
    textFieldQuery.error?.title ??
    intFieldQuery.error?.title ??
    enumFieldQuery.error?.title ??
    boolFieldQuery.error?.title ??
    dateTimeFieldQuery.error?.title ??
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
        enumFieldQuery.refetch(),
        boolFieldQuery.refetch(),
        dateTimeFieldQuery.refetch(),
      ])
    },
    saveTextFields,
    isSaving:
      createTextMutation.isPending ||
      updateTextMutation.isPending ||
      createIntMutation.isPending ||
      updateIntMutation.isPending ||
      createEnumMutation.isPending ||
      updateEnumMutation.isPending ||
      createBoolMutation.isPending ||
      updateBoolMutation.isPending ||
      createDateTimeMutation.isPending ||
      updateDateTimeMutation.isPending,
    saveErrorTitle:
      createTextMutation.error?.title ??
      updateTextMutation.error?.title ??
      createIntMutation.error?.title ??
      updateIntMutation.error?.title ??
      createEnumMutation.error?.title ??
      updateEnumMutation.error?.title ??
      createBoolMutation.error?.title ??
      updateBoolMutation.error?.title ??
      createDateTimeMutation.error?.title ??
      updateDateTimeMutation.error?.title ??
      null,
  }
}
