import type { RelationFieldDef } from '@/entities/field-def'
import { Button, Stack, Text } from '@/shared/ui'
import { useEntityRelationFilterField } from '../hooks/use-entity-relation-filter-field'

export interface EntityRelationFilterFieldProps {
  fieldDef: RelationFieldDef
  selectedTargetId: number | undefined
  onSelectTarget: (targetEntityId: number | undefined) => void
}

export function EntityRelationFilterField({
  fieldDef,
  selectedTargetId,
  onSelectTarget,
}: EntityRelationFilterFieldProps) {
  const { targetOptions, isLoading, isError, errorTitle, refetch } =
    useEntityRelationFilterField(fieldDef)

  const selectId = `relation-filter-${fieldDef.fieldKey}`

  if (isLoading) {
    return <Text muted>Loading {fieldDef.fieldKey} targets…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load {fieldDef.fieldKey} targets</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
        <Button variant="secondary" onClick={() => void refetch()}>
          Retry
        </Button>
      </Stack>
    )
  }

  return (
    <div className="flex flex-col gap-stack-xs">
      <label htmlFor={selectId} className="font-sans text-body font-medium text-text-primary">
        {fieldDef.fieldKey}
      </label>
      <select
        id={selectId}
        disabled={targetOptions.length === 0}
        value={selectedTargetId === undefined ? '' : String(selectedTargetId)}
        onChange={(event) => {
          const value = event.target.value
          onSelectTarget(value === '' ? undefined : Number(value))
        }}
        className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50"
      >
        <option value="">
          {targetOptions.length === 0 ? 'No targets available' : 'Any target'}
        </option>
        {targetOptions.map((option) => (
          <option key={String(option.id)} value={String(option.id)}>
            {option.label}
          </option>
        ))}
      </select>
    </div>
  )
}
