import { Controller } from 'react-hook-form'
import { FIELD_DATA_TYPES, type FieldDataType, type FieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import { useEditFieldDefForm } from '../hooks/use-create-field-def-form'

const DATA_TYPE_LABEL_KEYS: Record<FieldDataType, MessageKey> = {
  text: 'admin.fieldDefs.dataType.text',
  int: 'admin.fieldDefs.dataType.int',
  enum: 'admin.fieldDefs.dataType.enum',
  bool: 'admin.fieldDefs.dataType.bool',
  datetime: 'admin.fieldDefs.dataType.datetime',
}

export interface FieldDefEditFormProps {
  fieldDef: FieldDef
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: { fieldKey: string; dataType: FieldDataType }) => Promise<void>
  onCancel: () => void
}

export function FieldDefEditForm({
  fieldDef,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
  onCancel,
}: FieldDefEditFormProps) {
  const { t } = useTranslation()
  const {
    control,
    handleSubmit,
    formState: { errors },
  } = useEditFieldDefForm({
    fieldKey: fieldDef.fieldKey,
    dataType: fieldDef.dataType,
  })

  return (
    <form
      key={String(fieldDef.id)}
      className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm"
      onSubmit={(event) => {
        void handleSubmit(async (values) => {
          await onSubmit(values)
        })(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.fieldDefs.editForm.title')}
        </Text>
        <Controller
          name="fieldKey"
          control={control}
          render={({ field }) => (
            <Input
              id="field-def-edit-key"
              label={t('admin.fieldDefs.createForm.fieldKeyLabel')}
              error={errors.fieldKey?.message}
              autoComplete="off"
              disabled={isSubmitting}
              value={field.value}
              onChange={field.onChange}
              onBlur={field.onBlur}
            />
          )}
        />
        <Controller
          name="dataType"
          control={control}
          render={({ field }) => (
            <div className="flex flex-col gap-stack-xs">
              <label
                htmlFor="field-def-edit-data-type"
                className="font-sans text-body font-medium text-text-primary"
              >
                {t('admin.fieldDefs.createForm.dataTypeLabel')}
              </label>
              <select
                id="field-def-edit-data-type"
                disabled={isSubmitting}
                value={field.value}
                onChange={field.onChange}
                onBlur={field.onBlur}
                className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50"
              >
                {FIELD_DATA_TYPES.map((dataType) => (
                  <option key={dataType} value={dataType}>
                    {t(DATA_TYPE_LABEL_KEYS[dataType])}
                  </option>
                ))}
              </select>
              {errors.dataType?.message !== undefined ? (
                <span className="font-sans text-caption text-danger">
                  {errors.dataType.message}
                </span>
              ) : null}
            </div>
          )}
        />
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <div className="flex items-center gap-inline-sm">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? t('admin.fieldDefs.editForm.saving')
              : t('admin.fieldDefs.editForm.save')}
          </Button>
          <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
        </div>
      </Stack>
    </form>
  )
}
