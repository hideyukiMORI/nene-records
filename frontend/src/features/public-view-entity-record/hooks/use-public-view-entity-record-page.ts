import { useMemo } from 'react'
import type { FieldDataType } from '@/entities/field-def'
import { defaultFieldDefListParams, useFieldDefList } from '@/entities/field-def'
import { toEntityId, useEntity } from '@/entities/entity'
import { useBoolFieldList } from '@/entities/bool-field'
import { useDateTimeFieldList } from '@/entities/datetime-field'
import { useEnumFieldList } from '@/entities/enum-field'
import { useIntFieldList } from '@/entities/int-field'
import { useTextFieldList } from '@/entities/text-field'
import { formatFieldDisplayValue } from '@/shared/lib/format-field-display-value'

const FIELD_LIST_PARAMS = { limit: 100, offset: 0 } as const

export interface PublicFieldDisplay {
  fieldKey: string
  dataType: FieldDataType
  displayValue: string
}

export function usePublicViewEntityRecordPage(entityTypeId: number, entityId: number) {
  const listParams = useMemo(() => ({ ...FIELD_LIST_PARAMS, entityId }), [entityId])

  const entityQuery = useEntity(toEntityId(entityId))
  const fieldDefQuery = useFieldDefList(defaultFieldDefListParams(entityTypeId))
  const textFieldQuery = useTextFieldList(listParams)
  const intFieldQuery = useIntFieldList(listParams)
  const enumFieldQuery = useEnumFieldList(listParams)
  const boolFieldQuery = useBoolFieldList(listParams)
  const dateTimeFieldQuery = useDateTimeFieldList(listParams)

  const fields = useMemo((): PublicFieldDisplay[] => {
    const fieldDefs = fieldDefQuery.data?.items ?? []

    return fieldDefs.map((fieldDef) => {
      let raw: string | number | boolean | null = null

      switch (fieldDef.dataType) {
        case 'text':
          raw =
            textFieldQuery.data?.items.find((item) => item.fieldKey === fieldDef.fieldKey)?.value ??
            null
          break
        case 'int':
          raw =
            intFieldQuery.data?.items.find((item) => item.fieldKey === fieldDef.fieldKey)?.value ??
            null
          break
        case 'enum':
          raw =
            enumFieldQuery.data?.items.find((item) => item.fieldKey === fieldDef.fieldKey)?.value ??
            null
          break
        case 'bool':
          raw =
            boolFieldQuery.data?.items.find((item) => item.fieldKey === fieldDef.fieldKey)?.value ??
            null
          break
        case 'datetime':
          raw =
            dateTimeFieldQuery.data?.items.find((item) => item.fieldKey === fieldDef.fieldKey)
              ?.value ?? null
          break
      }

      return {
        fieldKey: fieldDef.fieldKey,
        dataType: fieldDef.dataType,
        displayValue: formatFieldDisplayValue(fieldDef.dataType, raw),
      }
    })
  }, [
    boolFieldQuery.data?.items,
    dateTimeFieldQuery.data?.items,
    enumFieldQuery.data?.items,
    fieldDefQuery.data?.items,
    intFieldQuery.data?.items,
    textFieldQuery.data?.items,
  ])

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
    fields,
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
  }
}
