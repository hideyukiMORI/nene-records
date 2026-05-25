import type { FieldDef } from '@/entities/field-def'
import type { Entity } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'
import { EntityTextFieldsForm } from './EntityTextFieldsForm'

export interface EditEntityTextFieldsViewProps {
  entity: Entity | null
  textFieldDefs: FieldDef[]
  initialValues: Record<string, string>
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isSaving: boolean
  saveErrorTitle: string | null
  onRetry: () => void
  onSave: (values: Record<string, string>) => Promise<void>
}

export function EditEntityTextFieldsView({
  entity,
  textFieldDefs,
  initialValues,
  isLoading,
  isError,
  errorTitle,
  isSaving,
  saveErrorTitle,
  onRetry,
  onSave,
}: EditEntityTextFieldsViewProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <Text muted>{t('admin.entityRecord.loading')}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.entityRecord.error')}</Text>
        <Text muted>{errorTitle ?? t('common.error.unknown')}</Text>
        <Button variant="secondary" onClick={onRetry}>
          {t('common.actions.retry')}
        </Button>
      </Stack>
    )
  }

  if (entity === null) {
    return <Text muted>{t('admin.entityRecord.notFound')}</Text>
  }

  return (
    <Stack gap="lg">
      <Text as="p" muted>
        {t('admin.entityRecord.id', { id: entity.id })}
      </Text>
      <EntityTextFieldsForm
        key={JSON.stringify(initialValues)}
        fieldDefs={textFieldDefs}
        defaultValues={initialValues}
        isSubmitting={isSaving}
        serverErrorTitle={saveErrorTitle}
        onSubmit={onSave}
      />
    </Stack>
  )
}
