import type { Entity } from '@/entities/entity'
import { Button, Stack, Text } from '@/shared/ui'
import type { PublicFieldRow } from '../hooks/use-public-view-entity-record-page'
import { PublicRecordFieldList } from './PublicRecordFieldList'

export interface PublicRecordDetailViewProps {
  entity: Entity | null
  fieldRows: PublicFieldRow[]
  entityTypeSlugById: Record<number, string>
  entityTypePatternById: Record<number, string | null | undefined>
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicRecordDetailView({
  entity,
  fieldRows,
  entityTypeSlugById,
  entityTypePatternById,
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
    <PublicRecordFieldList
      entity={entity}
      fieldRows={fieldRows}
      entityTypeSlugById={entityTypeSlugById}
      entityTypePatternById={entityTypePatternById}
    />
  )
}
