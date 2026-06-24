import { Controller } from 'react-hook-form'
import { FIELD_DATA_TYPES, type FieldDataType } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Button, Card, Input, Select, Stack, Text } from '@/shared/ui'
import {
  type CreateFieldDefFormValues,
  useCreateFieldDefForm,
} from '../hooks/use-create-field-def-form'
import { RelationFieldControls } from './RelationFieldControls'

const DATA_TYPE_LABEL_KEYS: Record<FieldDataType, MessageKey> = {
  text: 'admin.fieldDefs.dataType.text',
  markdown: 'admin.fieldDefs.dataType.markdown',
  html: 'admin.fieldDefs.dataType.html',
  bundle: 'admin.fieldDefs.dataType.bundle',
  int: 'admin.fieldDefs.dataType.int',
  enum: 'admin.fieldDefs.dataType.enum',
  bool: 'admin.fieldDefs.dataType.bool',
  datetime: 'admin.fieldDefs.dataType.datetime',
  image: 'admin.fieldDefs.dataType.image',
  file: 'admin.fieldDefs.dataType.file',
  relation: 'admin.fieldDefs.dataType.relation',
  blocks: 'admin.fieldDefs.dataType.blocks',
}

export interface FieldDefCreateFormProps {
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: CreateFieldDefFormValues) => Promise<void>
}

export function FieldDefCreateForm({
  isSubmitting,
  serverErrorTitle,
  onSubmit,
}: FieldDefCreateFormProps) {
  const { t } = useTranslation()
  const {
    control,
    handleSubmit,
    reset,
    formState: { errors },
  } = useCreateFieldDefForm()

  return (
    <Card
      as="form"
      onSubmit={(event) => {
        void handleSubmit(async (values) => {
          await onSubmit(values)
          reset({
            fieldKey: '',
            dataType: values.dataType,
            region: values.region,
            displayOrder: values.displayOrder,
            targetEntityTypeId: values.targetEntityTypeId,
            cardinality: values.cardinality,
          })
        })(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.fieldDefs.createForm.title')}
        </Text>
        <Controller
          name="fieldKey"
          control={control}
          render={({ field }) => (
            <Input
              id="field-def-key"
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
                htmlFor="field-def-data-type"
                className="font-sans text-body font-medium text-text-primary"
              >
                {t('admin.fieldDefs.createForm.dataTypeLabel')}
              </label>
              <Select
                id="field-def-data-type"
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
        <RelationFieldControls
          control={control}
          isSubmitting={isSubmitting}
          idPrefix="field-def"
          targetError={errors.targetEntityTypeId?.message}
        />
        <Controller
          name="region"
          control={control}
          render={({ field }) => (
            <Select
              id="field-def-region"
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
              id="field-def-order"
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
        <Button
          type="submit"
          disabled={isSubmitting}
          data-testid={isSubmitting ? 'submit-in-flight' : 'submit-idle'}
        >
          {isSubmitting
            ? t('admin.fieldDefs.createForm.submitting')
            : t('admin.fieldDefs.createForm.submit')}
        </Button>
      </Stack>
    </Card>
  )
}
