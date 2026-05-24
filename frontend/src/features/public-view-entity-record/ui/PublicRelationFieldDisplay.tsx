import { Link } from 'react-router-dom'
import type { RelationFieldDef } from '@/entities/field-def'
import { Button, Stack, Text } from '@/shared/ui'
import { usePublicRelationFieldDisplay } from '../hooks/use-public-relation-field-display'

export interface PublicRelationFieldDisplayProps {
  entityId: number
  fieldDef: RelationFieldDef
  entityTypeSlugById: Record<number, string>
}

export function PublicRelationFieldDisplay({
  entityId,
  fieldDef,
  entityTypeSlugById,
}: PublicRelationFieldDisplayProps) {
  const { targets, isLoading, isError, errorTitle, refetch } = usePublicRelationFieldDisplay(
    entityId,
    fieldDef,
    entityTypeSlugById,
  )

  if (isLoading) {
    return (
      <div className="flex flex-col gap-stack-xs">
        <Text as="dt" variant="heading-sm">
          {fieldDef.fieldKey}
        </Text>
        <Text as="dd" muted>
          Loading…
        </Text>
      </div>
    )
  }

  if (isError) {
    return (
      <div className="flex flex-col gap-stack-xs">
        <Text as="dt" variant="heading-sm">
          {fieldDef.fieldKey}
        </Text>
        <Stack gap="sm">
          <Text as="dd" muted>
            {errorTitle ?? 'Could not load relation'}
          </Text>
          <Button variant="secondary" size="sm" onClick={() => void refetch()}>
            Retry
          </Button>
        </Stack>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-stack-xs">
      <Text as="dt" variant="heading-sm">
        {fieldDef.fieldKey}
      </Text>
      {targets.length === 0 ? (
        <Text as="dd" muted>
          —
        </Text>
      ) : (
        <dd className="flex flex-col gap-stack-xs">
          {targets.map((target) => (
            <Link
              key={`${fieldDef.fieldKey}-${String(target.targetEntityId)}`}
              to={target.href}
              className="font-sans text-body text-accent hover:text-accent-hover"
            >
              {target.label}
            </Link>
          ))}
        </dd>
      )}
    </div>
  )
}
