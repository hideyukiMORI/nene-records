import { Link } from 'react-router-dom'
import type { RelationFieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'
import { useInverseRelationPanel } from '../hooks/use-inverse-relation-panel'

export interface InverseRelationPanelProps {
  fieldDef: RelationFieldDef
  targetEntityId: number
}

export function InverseRelationPanel({ fieldDef, targetEntityId }: InverseRelationPanelProps) {
  const { t } = useTranslation()
  const { sourceEntityTypeName, items, isLoading, isError, errorTitle, refetch } =
    useInverseRelationPanel(fieldDef, targetEntityId)

  const panelTitle =
    sourceEntityTypeName !== null
      ? `${sourceEntityTypeName} · ${fieldDef.fieldKey}`
      : fieldDef.fieldKey

  if (isLoading) {
    return <Text muted>{t('admin.inverseRelations.loadingPanel', { panelTitle })}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.inverseRelations.panelError', { panelTitle })}</Text>
        <Text muted>{errorTitle ?? t('common.error.unknown')}</Text>
        <Button variant="secondary" onClick={() => void refetch()}>
          {t('common.actions.retry')}
        </Button>
      </Stack>
    )
  }

  return (
    <Stack gap="sm">
      <Text as="h3" variant="heading-sm">
        {panelTitle}
      </Text>
      {items.length === 0 ? (
        <Text muted>
          {t('admin.inverseRelations.noReferences', { fieldKey: fieldDef.fieldKey })}
        </Text>
      ) : (
        <ul className="flex flex-col gap-stack-sm">
          {items.map((item) => (
            <li
              key={String(item.id)}
              className="flex items-center justify-between gap-inline-md rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
            >
              <Stack gap="xs">
                <Text as="span" variant="heading-sm">
                  {item.label}
                </Text>
                <Text as="span" muted>
                  #{String(item.id)}
                </Text>
              </Stack>
              <Link
                to={`/entity-types/${String(fieldDef.entityTypeId)}/entities/${String(item.id)}`}
              >
                <Button variant="secondary" size="sm">
                  {t('admin.inverseRelations.open')}
                </Button>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </Stack>
  )
}
