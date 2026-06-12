import { useState } from 'react'
import type { FieldDataType, FieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, EmptyState, Input, Stack, Text } from '@/shared/ui'
import { FileFieldInput } from './FileFieldInput'
import { ImageFieldInput } from './ImageFieldInput'
import { MarkdownFieldInput } from './MarkdownFieldInput'

export interface EntityTextFieldsFormProps {
  fieldDefs: FieldDef[]
  defaultValues: Record<string, string>
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: Record<string, string>) => Promise<void>
}

function getInputType(dataType: FieldDataType): 'text' | 'number' | 'datetime-local' {
  switch (dataType) {
    case 'int':
      return 'number'
    case 'datetime':
      return 'datetime-local'
    default:
      return 'text'
  }
}

export function EntityTextFieldsForm({
  fieldDefs,
  defaultValues,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
}: EntityTextFieldsFormProps) {
  const { t } = useTranslation()
  const [values, setValues] = useState(defaultValues)

  if (fieldDefs.length === 0) {
    return (
      <EmptyState
        title={t('admin.entityRecord.textFields.noFields.title')}
        description={t('admin.entityRecord.textFields.noFields.description')}
      />
    )
  }

  return (
    <Card
      as="form"
      onSubmit={(event) => {
        event.preventDefault()
        void onSubmit(values)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.entityRecord.textFields.title')}
        </Text>
        {fieldDefs.map((fieldDef) => {
          const fieldId = `record-field-${fieldDef.fieldKey}`
          const label = `${fieldDef.fieldKey} (${fieldDef.dataType})`

          if (fieldDef.dataType === 'image') {
            return (
              <ImageFieldInput
                key={fieldDef.fieldKey}
                id={fieldId}
                label={label}
                value={values[fieldDef.fieldKey] ?? ''}
                disabled={isSubmitting}
                onChange={(url) => {
                  setValues((current) => ({ ...current, [fieldDef.fieldKey]: url }))
                }}
              />
            )
          }

          if (fieldDef.dataType === 'file') {
            return (
              <FileFieldInput
                key={fieldDef.fieldKey}
                id={fieldId}
                label={label}
                value={values[fieldDef.fieldKey] ?? ''}
                disabled={isSubmitting}
                onChange={(url) => {
                  setValues((current) => ({ ...current, [fieldDef.fieldKey]: url }))
                }}
              />
            )
          }

          if (fieldDef.dataType === 'markdown') {
            return (
              <MarkdownFieldInput
                key={fieldDef.fieldKey}
                id={fieldId}
                label={label}
                value={values[fieldDef.fieldKey] ?? ''}
                disabled={isSubmitting}
                onChange={(val) => {
                  setValues((current) => ({ ...current, [fieldDef.fieldKey]: val }))
                }}
              />
            )
          }

          if (fieldDef.dataType === 'bool') {
            return (
              <div key={fieldDef.fieldKey} className="flex flex-col gap-stack-xs">
                <label
                  htmlFor={fieldId}
                  className="flex items-center gap-inline-sm font-sans text-body font-medium text-text-primary"
                >
                  <input
                    id={fieldId}
                    type="checkbox"
                    disabled={isSubmitting}
                    checked={values[fieldDef.fieldKey] === 'true'}
                    onChange={(event) => {
                      setValues((current) => ({
                        ...current,
                        [fieldDef.fieldKey]: event.target.checked ? 'true' : 'false',
                      }))
                    }}
                    className="size-4 rounded border border-border"
                  />
                  {label}
                </label>
              </div>
            )
          }

          return (
            <Input
              key={fieldDef.fieldKey}
              id={fieldId}
              label={label}
              type={getInputType(fieldDef.dataType)}
              autoComplete="off"
              disabled={isSubmitting}
              value={values[fieldDef.fieldKey] ?? ''}
              onChange={(event) => {
                setValues((current) => ({ ...current, [fieldDef.fieldKey]: event.target.value }))
              }}
            />
          )
        })}
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <div className="flex items-center gap-inline-md">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? t('admin.entityRecord.textFields.saving')
              : t('admin.entityRecord.textFields.save')}
          </Button>
        </div>
      </Stack>
    </Card>
  )
}
