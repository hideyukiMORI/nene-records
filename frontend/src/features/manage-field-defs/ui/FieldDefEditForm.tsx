import { Controller } from 'react-hook-form'
import { FIELD_DATA_TYPES, type FieldDataType, type FieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Card, Input, Select, Stack, Text } from '@/shared/ui'
import {
  type CreateFieldDefFormValues,
  useEditFieldDefForm,
} from '../hooks/use-create-field-def-form'

const DATA_TYPE_LABEL_KEYS: Record<FieldDataType, MessageKey> = {
  text: 'admin.fieldDefs.dataType.text',
  markdown: 'admin.fieldDefs.dataType.markdown',
  int: 'admin.fieldDefs.dataType.int',
  enum: 'admin.fieldDefs.dataType.enum',
  bool: 'admin.fieldDefs.dataType.bool',
  datetime: 'admin.fieldDefs.dataType.datetime',
  relation: 'admin.fieldDefs.dataType.relation',
}

export interface FieldDefEditFormProps {
  fieldDef: FieldDef
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: CreateFieldDefFormValues) => Promise<void>
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
    region: fieldDef.region ?? 'main',
    displayOrder: fieldDef.displayOrder,
  })

  return (
    <Card
      as="form"
      key={String(fieldDef.id)}
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
              <Select
                id="field-def-edit-data-type"
                disabled={isSubmitting}
                value={field.value}
                onChange={field.onChange}
                onBlur={field.onBlur}
              >
                {FIELD_DATA_TYPES.map((dataType) => (
                  <option key={dataType} value={dataType}>
                    {t(DATA_TYPE_LABEL_KEYS[dataType])}
                  </option>
                ))}
              </Select>
              {errors.dataType?.message !== undefined ? (
                <span className="font-sans text-caption text-danger">
                  {errors.dataType.message}
                </span>
              ) : null}
            </div>
          )}
        />
        <Controller
          name="region"
          control={control}
          render={({ field }) => (
            <Select
              id="field-def-edit-region"
              label={t('admin.region.label')}
              disabled={isSubmitting}
              value={field.value}
              onChange={field.onChange}
              onBlur={field.onBlur}
            >
              <option value="main">{t('admin.region.main')}</option>
              <option value="sidebar">{t('admin.region.sidebar')}</option>
              <option value="aside">{t('admin.region.aside')}</option>
            </Select>
          )}
        />
        <Controller
          name="displayOrder"
          control={control}
          render={({ field }) => (
            <Input
              id="field-def-edit-order"
              type="number"
              label={t('admin.fieldDefs.displayOrder')}
              disabled={isSubmitting}
              value={String(field.value)}
              onChange={(e) => {
                field.onChange(Number(e.target.value) || 0)
              }}
              onBlur={field.onBlur}
            />
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
    </Card>
  )
}
