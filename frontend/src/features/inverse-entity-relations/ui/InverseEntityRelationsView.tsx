import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'
import { useInverseRelationFieldDefs } from '../hooks/use-inverse-relation-field-defs'
import { InverseRelationPanel } from './InverseRelationPanel'

export interface InverseEntityRelationsViewProps {
  entityId: number
  entityTypeId: number
}

export function InverseEntityRelationsView({
  entityId,
  entityTypeId,
}: InverseEntityRelationsViewProps) {
  const { t } = useTranslation()
  const { inverseFieldDefs, isLoading, isError, errorTitle } =
    useInverseRelationFieldDefs(entityTypeId)

  if (isLoading) {
    return <Text muted>{t('admin.inverseRelations.loadingPanel', { panelTitle: '…' })}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.inverseRelations.title')}</Text>
        <Text muted>{errorTitle ?? t('common.error.unknown')}</Text>
      </Stack>
    )
  }

  if (inverseFieldDefs.length === 0) {
    return null
  }

  return (
    <Stack gap="lg">
      <Text as="h2" variant="heading-sm">
        {t('admin.inverseRelations.title')}
      </Text>
      {inverseFieldDefs.map((fieldDef) => (
        <InverseRelationPanel
          key={String(fieldDef.id)}
          fieldDef={fieldDef}
          targetEntityId={entityId}
        />
      ))}
    </Stack>
  )
}
