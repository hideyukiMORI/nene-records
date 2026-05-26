import type { Entity } from '@/entities/entity'
import { isMarkdownBodyField } from '@/shared/lib/is-markdown-body-field'
import { Button, Stack, Text } from '@/shared/ui'
import { PublicMarkdownContent } from '@/shared/ui/markdown'
import type { PublicFieldRow } from '../hooks/use-public-view-entity-record-page'
import { PublicRelationFieldDisplay } from './PublicRelationFieldDisplay'

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
    <dl className="flex flex-col gap-stack-md">
      {fieldRows.map((row) => {
        if (row.kind === 'relation') {
          return (
            <PublicRelationFieldDisplay
              key={row.fieldDef.fieldKey}
              entityId={Number(entity.id)}
              fieldDef={row.fieldDef}
              entityTypeSlugById={entityTypeSlugById}
              entityTypePatternById={entityTypePatternById}
            />
          )
        }

        return (
          <div key={row.fieldKey} className="flex flex-col gap-stack-xs">
            <Text as="dt" variant="heading-sm">
              {row.fieldKey}
            </Text>
            {isMarkdownBodyField(row.fieldKey) && row.dataType === 'text' ? (
              <dd>
                <PublicMarkdownContent
                  markdown={row.displayValue === '—' ? '' : row.displayValue}
                />
              </dd>
            ) : (
              <Text as="dd" muted>
                {row.displayValue}
              </Text>
            )}
          </div>
        )
      })}
    </dl>
  )
}
