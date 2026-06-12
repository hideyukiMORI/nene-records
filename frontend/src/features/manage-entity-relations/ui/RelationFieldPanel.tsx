import type { RelationFieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, ErrorState, LoadingState, Select, Stack, Text } from '@/shared/ui'
import { useRelationFieldPanel } from '../hooks/use-relation-field-panel'

export interface RelationFieldPanelProps {
  entityId: number
  fieldDef: RelationFieldDef
}

export function RelationFieldPanel({ entityId, fieldDef }: RelationFieldPanelProps) {
  const { t } = useTranslation()
  const {
    attachedRelations,
    targetLabels,
    availableTargetIds,
    selectedTargetId,
    setSelectedTargetId,
    isLoading,
    isError,
    errorTitle,
    isAttaching,
    attachErrorTitle,
    isDetaching,
    attachTarget,
    detachTarget,
    refetch,
  } = useRelationFieldPanel(entityId, fieldDef)

  const attachLabel =
    fieldDef.cardinality === 'one' ? t('admin.relations.setTarget') : t('admin.relations.addTarget')
  const selectId = `relation-target-${fieldDef.fieldKey}`

  if (isLoading) {
    return (
      <LoadingState>
        {t('admin.relations.loadingField', { fieldKey: fieldDef.fieldKey })}
      </LoadingState>
    )
  }

  if (isError) {
    return (
      <ErrorState
        title={t('admin.relations.fieldError', { fieldKey: fieldDef.fieldKey })}
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={() => void refetch()}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  return (
    <Stack gap="md">
      <Stack gap="xs">
        <Text as="h3" variant="heading-sm">
          {fieldDef.fieldKey}
        </Text>
        <Text muted>
          {t('admin.relations.relationType', {
            cardinality: fieldDef.cardinality,
            targetTypeId: fieldDef.targetEntityTypeId,
          })}
        </Text>
      </Stack>
      {attachedRelations.length === 0 ? (
        <Text muted>{t('admin.relations.noTargets')}</Text>
      ) : (
        <ul className="flex flex-col gap-stack-sm">
          {attachedRelations.map((relation) => (
            <Card
              as="li"
              key={`${relation.fieldKey}-${String(relation.targetEntityId)}`}
              padding="row"
              className="flex items-center justify-between gap-inline-md"
            >
              <Stack gap="xs">
                <Text as="span" variant="heading-sm">
                  {targetLabels[String(relation.targetEntityId)] ??
                    t('admin.entityRecord.id', { id: relation.targetEntityId })}
                </Text>
                <Text as="span" muted>
                  #{String(relation.targetEntityId)}
                </Text>
              </Stack>
              <Button
                variant="secondary"
                size="sm"
                disabled={isDetaching}
                onClick={() => {
                  void detachTarget(relation)
                }}
              >
                {t('admin.relations.remove')}
              </Button>
            </Card>
          ))}
        </ul>
      )}
      <Stack gap="sm">
        <div className="flex flex-col gap-stack-xs">
          <label htmlFor={selectId} className="font-sans text-body font-medium text-text-primary">
            {attachLabel}
          </label>
          <Select
            id={selectId}
            disabled={isAttaching || availableTargetIds.length === 0}
            value={selectedTargetId}
            onChange={(event) => {
              setSelectedTargetId(event.target.value)
            }}
          >
            <option value="">
              {availableTargetIds.length === 0
                ? t('admin.relations.noTargetsAvailable')
                : t('admin.relations.selectTarget')}
            </option>
            {availableTargetIds.map((targetId) => (
              <option key={String(targetId)} value={String(targetId)}>
                {targetLabels[String(targetId)] ?? t('admin.entityRecord.id', { id: targetId })}
              </option>
            ))}
          </Select>
        </div>
        {attachErrorTitle !== null ? <Text muted>{attachErrorTitle}</Text> : null}
        <Button
          variant="secondary"
          disabled={isAttaching || selectedTargetId === ''}
          onClick={() => {
            void attachTarget()
          }}
        >
          {isAttaching ? t('admin.relations.saving') : attachLabel}
        </Button>
      </Stack>
    </Stack>
  )
}
