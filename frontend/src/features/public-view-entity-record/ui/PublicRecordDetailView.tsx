import type { Entity } from '@/entities/entity'
import { Button, Stack, Text } from '@/shared/ui'
import type { PublicFieldDisplay } from '../hooks/use-public-view-entity-record-page'

export interface PublicRecordDetailViewProps {
  entity: Entity | null
  fields: PublicFieldDisplay[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicRecordDetailView({
  entity,
  fields,
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

  if (fields.length === 0) {
    return <Text muted>No fields defined for this record.</Text>
  }

  return (
    <dl className="flex flex-col gap-stack-md">
      {fields.map((field) => (
        <div key={field.fieldKey} className="flex flex-col gap-stack-xs">
          <Text as="dt" variant="heading-sm">
            {field.fieldKey}
          </Text>
          <Text as="dd" muted>
            {field.displayValue}
          </Text>
        </div>
      ))}
    </dl>
  )
}
