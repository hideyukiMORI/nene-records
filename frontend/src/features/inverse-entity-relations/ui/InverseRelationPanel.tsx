import { Link } from 'react-router-dom'
import type { RelationFieldDef } from '@/entities/field-def'
import { Button, Stack, Text } from '@/shared/ui'
import { useInverseRelationPanel } from '../hooks/use-inverse-relation-panel'

export interface InverseRelationPanelProps {
  fieldDef: RelationFieldDef
  targetEntityId: number
}

export function InverseRelationPanel({ fieldDef, targetEntityId }: InverseRelationPanelProps) {
  const { sourceEntityTypeName, items, isLoading, isError, errorTitle, refetch } =
    useInverseRelationPanel(fieldDef, targetEntityId)

  const panelTitle =
    sourceEntityTypeName !== null
      ? `${sourceEntityTypeName} · ${fieldDef.fieldKey}`
      : fieldDef.fieldKey

  if (isLoading) {
    return <Text muted>Loading {panelTitle}…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load {panelTitle}</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
        <Button variant="secondary" onClick={() => void refetch()}>
          Retry
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
        <Text muted>No records reference this target via {fieldDef.fieldKey}.</Text>
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
                  Open
                </Button>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </Stack>
  )
}
