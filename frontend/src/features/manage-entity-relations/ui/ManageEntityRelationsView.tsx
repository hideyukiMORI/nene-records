import { useMemo } from 'react'
import {
  defaultFieldDefListParams,
  isRelationFieldDef,
  useFieldDefList,
} from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'
import { RelationFieldPanel } from './RelationFieldPanel'

export interface ManageEntityRelationsViewProps {
  entityId: number
  entityTypeId: number
}

export function ManageEntityRelationsView({
  entityId,
  entityTypeId,
}: ManageEntityRelationsViewProps) {
  const { t } = useTranslation()
  const fieldDefQuery = useFieldDefList(defaultFieldDefListParams(entityTypeId))

  const relationFieldDefs = useMemo(
    () => (fieldDefQuery.data?.items ?? []).filter(isRelationFieldDef),
    [fieldDefQuery.data?.items],
  )

  if (fieldDefQuery.isLoading) {
    return <Text muted>{t('admin.relations.loadingField', { fieldKey: '…' })}</Text>
  }

  if (fieldDefQuery.isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.relations.title')}</Text>
        <Text muted>{fieldDefQuery.error.title}</Text>
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
