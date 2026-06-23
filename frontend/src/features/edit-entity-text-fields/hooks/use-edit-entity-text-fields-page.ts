import { useCallback, useMemo, useState } from 'react'
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
import {
  useBlocksFieldList,
  useCreateBlocksField,
  useUpdateBlocksField,
  type BlocksField,
} from '@/entities/block'

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

/**
 * Create-or-update a single field row: run `update` with the existing row's id
 * when one exists, otherwise `create`. Collapses the per-type create/update
 * branch that each field type repeated in {@link saveTextFields}.
 */
async function upsert<Id>(
  existing: { id: Id } | undefined,
  update: (id: Id) => Promise<unknown>,
  create: () => Promise<unknown>,
): Promise<void> {
  if (existing !== undefined) {
    await update(existing.id)
  } else {
    await create()
  }
}

export function useEditEntityTextFieldsPage(entityTypeId: number, entityId: number) {
  const [selectedLocale, setSelectedLocale] = useState<string | null>(null)

  const listParams = useMemo(() => ({ ...FIELD_LIST_PARAMS, entityId }), [entityId])

  const entityQuery = useEntity(toEntityId(entityId))
  const fieldDefQuery = useFieldDefList(defaultFieldDefListParams(entityTypeId))
  const textFieldQuery = useTextFieldList(listParams)
  const intFieldQuery = useIntFieldList(listParams)
  const enumFieldQuery = useEnumFieldList(listParams)
  const boolFieldQuery = useBoolFieldList(listParams)
  const dateTimeFieldQuery = useDateTimeFieldList(listParams)
  const blocksFieldQuery = useBlocksFieldList(listParams)
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
  const createBlocksMutation = useCreateBlocksField()
  const updateBlocksMutation = useUpdateBlocksField()

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

  // Text fields filtered to the currently selected locale
  const textFieldsForLocale = useMemo(
    () => textFieldsForEntity.filter((f) => f.locale === selectedLocale),
    [textFieldsForEntity, selectedLocale],
  )

  // Unique locales present in the fetched text fields (excluding null = default)
  const availableLocales = useMemo(() => {
    const locales = new Set(textFieldsForEntity.map((f) => f.locale))
    return Array.from(locales)
      .filter((l): l is string => l !== null)
      .sort()
  }, [textFieldsForEntity])

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

  const blocksFieldsForEntity = useMemo((): BlocksField[] => {
    return blocksFieldQuery.data?.items ?? []
  }, [blocksFieldQuery.data?.items])

  const blocksFieldsForLocale = useMemo(
    () => blocksFieldsForEntity.filter((f) => f.locale === selectedLocale),
    [blocksFieldsForEntity, selectedLocale],
  )

  const initialValues = useMemo((): Record<string, string> => {
    return Object.fromEntries(
      editableFieldDefs.map((fieldDef) => {
        switch (fieldDef.dataType) {
          case 'text':
          case 'markdown':
          case 'html':
          case 'bundle':
          case 'image': {
            const existing = textFieldsForLocale.find((item) => item.fieldKey === fieldDef.fieldKey)
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
          case 'blocks': {
            const existing = blocksFieldsForLocale.find(
              (item) => item.fieldKey === fieldDef.fieldKey,
            )
            return [fieldDef.fieldKey, existing?.value ?? '']
          }
          default:
            return [fieldDef.fieldKey, '']
        }
      }),
    )
  }, [
    blocksFieldsForLocale,
    boolFieldsForEntity,
    dateTimeFieldsForEntity,
    editableFieldDefs,
    enumFieldsForEntity,
    intFieldsForEntity,
    textFieldsForLocale,
  ])

  const saveTextFields = useCallback(
    async (values: Record<string, string>) => {
      for (const fieldDef of editableFieldDefs) {
        const rawValue = values[fieldDef.fieldKey] ?? ''

        switch (fieldDef.dataType) {
          case 'text':
          case 'markdown':
          case 'html':
          case 'bundle':
          case 'image': {
            const existing = textFieldsForLocale.find((item) => item.fieldKey === fieldDef.fieldKey)
            await upsert(
              existing,
              (id) =>
                updateTextMutation.mutateAsync({
                  id,
                  input: { fieldKey: fieldDef.fieldKey, value: rawValue, locale: selectedLocale },
                }),
              () =>
                createTextMutation.mutateAsync({
                  entityId,
                  fieldKey: fieldDef.fieldKey,
                  value: rawValue,
                  locale: selectedLocale,
                }),
            )
            break
          }
          case 'int': {
            const intValue = parseIntFieldValue(rawValue)
            const existing = intFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
            await upsert(
              existing,
              (id) =>
                updateIntMutation.mutateAsync({
                  id,
                  input: { fieldKey: fieldDef.fieldKey, value: intValue },
                }),
              () =>
                createIntMutation.mutateAsync({
                  entityId,
                  fieldKey: fieldDef.fieldKey,
                  value: intValue,
                }),
            )
            break
          }
          case 'enum': {
            const existing = enumFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
            await upsert(
              existing,
              (id) =>
                updateEnumMutation.mutateAsync({
                  id,
                  input: { fieldKey: fieldDef.fieldKey, value: rawValue },
                }),
              () =>
                createEnumMutation.mutateAsync({
                  entityId,
                  fieldKey: fieldDef.fieldKey,
                  value: rawValue,
                }),
            )
            break
          }
          case 'bool': {
            const boolValue = parseBoolFieldValue(rawValue)
            const existing = boolFieldsForEntity.find((item) => item.fieldKey === fieldDef.fieldKey)
            await upsert(
              existing,
              (id) =>
                updateBoolMutation.mutateAsync({
                  id,
                  input: { fieldKey: fieldDef.fieldKey, value: boolValue },
                }),
              () =>
                createBoolMutation.mutateAsync({
                  entityId,
                  fieldKey: fieldDef.fieldKey,
                  value: boolValue,
                }),
            )
            break
          }
          case 'datetime': {
            const isoValue = datetimeLocalToIso(rawValue)
            const existing = dateTimeFieldsForEntity.find(
              (item) => item.fieldKey === fieldDef.fieldKey,
            )
            await upsert(
              existing,
              (id) =>
                updateDateTimeMutation.mutateAsync({
                  id,
                  input: { fieldKey: fieldDef.fieldKey, value: isoValue },
                }),
              () =>
                createDateTimeMutation.mutateAsync({
                  entityId,
                  fieldKey: fieldDef.fieldKey,
                  value: isoValue,
                }),
            )
            break
          }
          case 'blocks': {
            // value is the JSON blocks document; normalize empty to a valid array.
            const normalized = rawValue.trim() === '' ? '[]' : rawValue
            const existing = blocksFieldsForLocale.find(
              (item) => item.fieldKey === fieldDef.fieldKey,
            )
            await upsert(
              existing,
              (id) =>
                updateBlocksMutation.mutateAsync({
                  id,
                  input: { fieldKey: fieldDef.fieldKey, value: normalized, locale: selectedLocale },
                }),
              // a brand-new blocks field with an empty document needs no row yet
              () =>
                normalized === '[]'
                  ? Promise.resolve()
                  : createBlocksMutation.mutateAsync({
                      entityId,
                      fieldKey: fieldDef.fieldKey,
                      value: normalized,
                      locale: selectedLocale,
                    }),
            )
            break
          }
        }
      }
    },
    [
      blocksFieldsForLocale,
      boolFieldsForEntity,
      createBlocksMutation,
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
      selectedLocale,
      textFieldsForLocale,
      updateBlocksMutation,
      updateBoolMutation,
      updateDateTimeMutation,
      updateEnumMutation,
      updateIntMutation,
      updateTextMutation,
    ],
  )

  // All loaded queries / fired mutations, aggregated uniformly instead of by a
  // long ||/?? chain that had to be extended for every new field type.
  const queries = [
    entityQuery,
    fieldDefQuery,
    textFieldQuery,
    intFieldQuery,
    enumFieldQuery,
    boolFieldQuery,
    dateTimeFieldQuery,
    blocksFieldQuery,
  ]
  const mutations = [
    createTextMutation,
    updateTextMutation,
    createIntMutation,
    updateIntMutation,
    createEnumMutation,
    updateEnumMutation,
    createBoolMutation,
    updateBoolMutation,
    createDateTimeMutation,
    updateDateTimeMutation,
    createBlocksMutation,
    updateBlocksMutation,
  ]
  const firstTitle = (errors: readonly ({ title: string } | null)[]): string | null =>
    errors.find((error) => error !== null)?.title ?? null

  const isLoading = queries.some((query) => query.isLoading)
  const isError = queries.some((query) => query.isError)
  const errorTitle = firstTitle(queries.map((query) => query.error))
  const isSaving = mutations.some((mutation) => mutation.isPending)
  const saveErrorTitle = firstTitle(mutations.map((mutation) => mutation.error))

  return {
    entity: entityQuery.data ?? null,
    textFieldDefs: editableFieldDefs,
    initialValues,
    selectedLocale,
    availableLocales,
    setLocale: setSelectedLocale,
    isLoading,
    isError,
    errorTitle,
    refetch: async () => {
      await Promise.all(queries.map((query) => query.refetch()))
    },
    saveTextFields,
    isSaving,
    saveErrorTitle,
  }
}
