import { Link } from 'react-router-dom'
import type { RelationFieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, ErrorState, LoadingState, Stack, Text } from '@/shared/ui'
import { useInverseRelationPanel } from '../hooks/use-inverse-relation-panel'

export interface InverseRelationPanelProps {
  fieldDef: RelationFieldDef
  targetEntityId: number
}

export function InverseRelationPanel({ fieldDef, targetEntityId }: InverseRelationPanelProps) {
  const { t } = useTranslation()
  const {
    sourceEntityTypeName,
    sourceEntityTypeSlug,
    items,
    isLoading,
    isError,
    errorTitle,
    refetch,
  } = useInverseRelationPanel(fieldDef, targetEntityId)

  const panelTitle =
    sourceEntityTypeName !== null
      ? `${sourceEntityTypeName} · ${fieldDef.fieldKey}`
      : fieldDef.fieldKey

  if (isLoading) {
    return <LoadingState>{t('admin.inverseRelations.loadingPanel', { panelTitle })}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title={t('admin.inverseRelations.panelError', { panelTitle })}
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={() => void refetch()}
        retryLabel={t('common.actions.retry')}
      />
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
            <Card
              as="li"
              key={String(item.id)}
              padding="row"
              className="flex items-center justify-between gap-inline-md"
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
                to={
                  sourceEntityTypeSlug !== null
                    ? `/admin/${sourceEntityTypeSlug}/${String(item.id)}`
                    : `/admin/entity-types/${String(fieldDef.entityTypeId)}/entities/${String(item.id)}`
                }
              >
                <Button variant="secondary" size="sm">
                  {t('admin.inverseRelations.open')}
                </Button>
              </Link>
            </Card>
          ))}
        </ul>
      )}
    </Stack>
  )
}
