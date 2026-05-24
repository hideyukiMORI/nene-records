import type { Entity } from '@/entities/entity'
import { Button, Stack, Text } from '@/shared/ui'
import type { PublicFieldRow } from '../hooks/use-public-view-entity-record-page'
import { PublicRelationFieldDisplay } from './PublicRelationFieldDisplay'

export interface PublicRecordDetailViewProps {
  entity: Entity | null
  fieldRows: PublicFieldRow[]
  entityTypeSlugById: Record<number, string>
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicRecordDetailView({
  entity,
  fieldRows,
  entityTypeSlugById,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: PublicRecordDetailViewProps) {
  if (isLoading) {
    return <Text muted>Loading…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load record</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
        <Button variant="secondary" onClick={onRetry}>
          Retry
        </Button>
      </Stack>
    )
  }

  if (entity === null) {
    return <Text muted>Record not found.</Text>
  }

  if (fieldRows.length === 0) {
    return <Text muted>No fields defined for this record.</Text>
  }

  return (
    <dl className="flex flex-col gap-stack-md">
      {fieldRows.map((row) => {
        if (row.kind === 'relation') {
          return (
            <PublicRelationFieldDisplay
              key={row.fieldDef.fieldKey}
              entityId={Number(entity.id)}
              fieldDef={row.fieldDef}
              entityTypeSlugById={entityTypeSlugById}
            />
          )
        }

        return (
          <div key={row.fieldKey} className="flex flex-col gap-stack-xs">
            <Text as="dt" variant="heading-sm">
              {row.fieldKey}
            </Text>
            <Text as="dd" muted>
              {row.displayValue}
            </Text>
          </div>
        )
      })}
    </dl>
  )
}
