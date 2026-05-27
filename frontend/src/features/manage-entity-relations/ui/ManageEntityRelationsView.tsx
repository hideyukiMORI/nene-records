import type { RelationFieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'
import { RelationFieldPanel } from './RelationFieldPanel'

export interface ManageEntityRelationsViewProps {
  entityId: number
  relationFieldDefs: RelationFieldDef[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
}

export function ManageEntityRelationsView({
  entityId,
  relationFieldDefs,
  isLoading,
  isError,
  errorTitle,
}: ManageEntityRelationsViewProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <Text muted>{t('admin.relations.loadingField', { fieldKey: '…' })}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.relations.title')}</Text>
        <Text muted>{errorTitle}</Text>
      </Stack>
    )
  }

  if (relationFieldDefs.length === 0) {
    return null
  }

  return (
    <Stack gap="lg">
      <Text as="h2" variant="heading-sm">
        {t('admin.relations.title')}
      </Text>
      {relationFieldDefs.map((fieldDef) => (
        <RelationFieldPanel key={String(fieldDef.id)} entityId={entityId} fieldDef={fieldDef} />
      ))}
    </Stack>
  )
}
