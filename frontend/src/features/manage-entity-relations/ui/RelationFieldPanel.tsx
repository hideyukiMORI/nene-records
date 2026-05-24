import type { RelationFieldDef } from '@/entities/field-def'
import { Button, Stack, Text } from '@/shared/ui'
import { useRelationFieldPanel } from '../hooks/use-relation-field-panel'

export interface RelationFieldPanelProps {
  entityId: number
  fieldDef: RelationFieldDef
}

export function RelationFieldPanel({ entityId, fieldDef }: RelationFieldPanelProps) {
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

  const attachLabel = fieldDef.cardinality === 'one' ? 'Set target' : 'Add target'
  const selectId = `relation-target-${fieldDef.fieldKey}`

  if (isLoading) {
    return <Text muted>Loading {fieldDef.fieldKey}…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load {fieldDef.fieldKey}</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
        <Button variant="secondary" onClick={() => void refetch()}>
          Retry
        </Button>
      </Stack>
    )
  }

  return (
    <Stack gap="md">
      <Stack gap="xs">
        <Text as="h3" variant="heading-sm">
          {fieldDef.fieldKey}
        </Text>
        <Text muted>
          relation · {fieldDef.cardinality} · target type #{String(fieldDef.targetEntityTypeId)}
        </Text>
      </Stack>
      {attachedRelations.length === 0 ? (
        <Text muted>No targets linked yet.</Text>
      ) : (
        <ul className="flex flex-col gap-stack-sm">
          {attachedRelations.map((relation) => (
            <li
              key={`${relation.fieldKey}-${String(relation.targetEntityId)}`}
              className="flex items-center justify-between gap-inline-md rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
            >
              <Stack gap="xs">
                <Text as="span" variant="heading-sm">
                  {targetLabels[String(relation.targetEntityId)] ??
                    `Record #${String(relation.targetEntityId)}`}
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
                Remove
              </Button>
            </li>
          ))}
        </ul>
      )}
      <Stack gap="sm">
        <div className="flex flex-col gap-stack-xs">
          <label htmlFor={selectId} className="font-sans text-body font-medium text-text-primary">
            {attachLabel}
          </label>
          <select
            id={selectId}
            disabled={isAttaching || availableTargetIds.length === 0}
            value={selectedTargetId}
            onChange={(event) => {
              setSelectedTargetId(event.target.value)
            }}
            className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50"
          >
            <option value="">
              {availableTargetIds.length === 0 ? 'No targets available' : 'Select target…'}
            </option>
            {availableTargetIds.map((targetId) => (
              <option key={String(targetId)} value={String(targetId)}>
                {targetLabels[String(targetId)] ?? `Record #${String(targetId)}`}
              </option>
            ))}
          </select>
        </div>
        {attachErrorTitle !== null ? <Text muted>{attachErrorTitle}</Text> : null}
        <Button
          variant="secondary"
          disabled={isAttaching || selectedTargetId === ''}
          onClick={() => {
            void attachTarget()
          }}
        >
          {isAttaching ? 'Saving…' : attachLabel}
        </Button>
      </Stack>
    </Stack>
  )
}
