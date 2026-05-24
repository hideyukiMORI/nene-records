import type { FieldDef } from '@/entities/field-def'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

const DATA_TYPE_LABELS: Record<FieldDef['dataType'], string> = {
  text: 'Text',
  int: 'Integer',
  enum: 'Enum',
  bool: 'Boolean',
  datetime: 'Date & time',
}

export interface FieldDefListPanelProps {
  items: FieldDef[]
  canManageSchema: boolean
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  onRetry: () => void
  onEdit: (fieldDef: FieldDef) => void
  onDelete: (fieldDef: FieldDef) => void
}

export function FieldDefListPanel({
  items,
  canManageSchema,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  onRetry,
  onEdit,
  onDelete,
}: FieldDefListPanelProps) {
  if (isLoading) {
    return <Text muted>Loading fields…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load fields</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
        <Button variant="secondary" onClick={onRetry}>
          Retry
        </Button>
      </Stack>
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title="No fields yet"
        description="Add your first field definition using the form above."
      />
    )
  }

  return (
    <ul className="flex flex-col gap-stack-sm">
      {items.map((item) => (
        <li
          key={String(item.id)}
          className="flex items-center justify-between gap-inline-md rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
        >
          <Stack gap="xs">
            <Text as="span" variant="heading-sm">
              {item.fieldKey}
            </Text>
            <Text as="span" muted>
              {DATA_TYPE_LABELS[item.dataType]}
            </Text>
          </Stack>
          <div className="flex items-center gap-inline-sm">
            {canManageSchema ? (
              <>
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => {
                    onEdit(item)
                  }}
                >
                  Edit
                </Button>
                <Button
                  variant="danger"
                  size="sm"
                  disabled={isDeleting}
                  onClick={() => {
                    onDelete(item)
                  }}
                >
                  Delete
                </Button>
              </>
            ) : null}
          </div>
        </li>
      ))}
    </ul>
  )
}
