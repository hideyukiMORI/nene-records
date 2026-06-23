import { Controller, useForm, type ControllerRenderProps } from 'react-hook-form'
import type { FieldDataType, FieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, EmptyState, Input, Stack, Text, Textarea } from '@/shared/ui'
import { BlocksFieldEditor } from './BlocksFieldEditor'
import { BundleFieldEditor } from './BundleFieldEditor'
import { FileFieldInput } from './FileFieldInput'
import { ImageFieldInput } from './ImageFieldInput'
import { MarkdownFieldInput } from './MarkdownFieldInput'

type RecordFormValues = Record<string, string>
type FieldRenderProps = ControllerRenderProps<RecordFormValues, string>

export interface EntityTextFieldsFormProps {
  fieldDefs: FieldDef[]
  /** Server-derived values; React Hook Form syncs to these while preserving dirty edits. */
  values: RecordFormValues
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: RecordFormValues) => Promise<void>
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
  values,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
}: EntityTextFieldsFormProps) {
  const { t } = useTranslation()
  // Controlled by server data via `values`; `keepDirtyValues` preserves in-progress
  // edits when a background refetch updates the server snapshot (no remount, no data loss).
  const { control, handleSubmit } = useForm<RecordFormValues>({
    values,
    resetOptions: { keepDirtyValues: true },
  })

  if (fieldDefs.length === 0) {
    return (
      <EmptyState
        title={t('admin.entityRecord.textFields.noFields.title')}
        description={t('admin.entityRecord.textFields.noFields.description')}
      />
    )
  }

  const renderField = (fieldDef: FieldDef, field: FieldRenderProps) => {
    const fieldId = `record-field-${fieldDef.fieldKey}`
    const label = `${fieldDef.fieldKey} (${fieldDef.dataType})`
    const value = field.value

    switch (fieldDef.dataType) {
      case 'image':
        return (
          <ImageFieldInput
            id={fieldId}
            label={label}
            value={value}
            disabled={isSubmitting}
            onChange={field.onChange}
          />
        )
      case 'file':
        return (
          <FileFieldInput
            id={fieldId}
            label={label}
            value={value}
            disabled={isSubmitting}
            onChange={field.onChange}
          />
        )
      case 'markdown':
        return (
          <MarkdownFieldInput
            id={fieldId}
            label={label}
            value={value}
            disabled={isSubmitting}
            onChange={field.onChange}
          />
        )
      case 'blocks':
        return (
          <BlocksFieldEditor
            id={fieldId}
            label={label}
            value={value}
            disabled={isSubmitting}
            onChange={field.onChange}
          />
        )
      case 'bundle':
        return (
          <BundleFieldEditor
            id={fieldId}
            label={label}
            value={value}
            disabled={isSubmitting}
            onChange={field.onChange}
          />
        )
      case 'html':
        return (
          <div className="flex flex-col gap-stack-xs">
            <label htmlFor={fieldId} className="font-sans text-body font-medium text-text-primary">
              {label}
            </label>
            <Textarea
              id={fieldId}
              rows={10}
              size="sm"
              mono
              disabled={isSubmitting}
              value={value}
              onChange={field.onChange}
              onBlur={field.onBlur}
            />
            <span className="font-sans text-caption text-text-muted">
              {t('admin.fieldDefs.html.hint')}
            </span>
          </div>
        )
      case 'bool':
        return (
          <div className="flex flex-col gap-stack-xs">
            <label
              htmlFor={fieldId}
              className="flex items-center gap-inline-sm font-sans text-body font-medium text-text-primary"
            >
              <input
                id={fieldId}
                type="checkbox"
                disabled={isSubmitting}
                checked={value === 'true'}
                onChange={(event) => {
                  field.onChange(event.target.checked ? 'true' : 'false')
                }}
                onBlur={field.onBlur}
                className="size-4 rounded border border-border"
              />
              {label}
            </label>
          </div>
        )
      default:
        return (
          <Input
            id={fieldId}
            label={label}
            type={getInputType(fieldDef.dataType)}
            autoComplete="off"
            disabled={isSubmitting}
            value={value}
            onChange={field.onChange}
            onBlur={field.onBlur}
          />
        )
    }
  }

  return (
    <Card
      as="form"
      onSubmit={(event) => {
        void handleSubmit(onSubmit)(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.entityRecord.textFields.title')}
        </Text>
        {fieldDefs.map((fieldDef) => (
          <Controller
            key={fieldDef.fieldKey}
            name={fieldDef.fieldKey}
            control={control}
            render={({ field }) => renderField(fieldDef, field)}
          />
        ))}
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
