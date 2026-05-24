import { Stack, Text } from '@/shared/ui'
import { useInverseRelationFieldDefs } from '../hooks/use-inverse-relation-field-defs'
import { InverseRelationPanel } from './InverseRelationPanel'

export interface InverseEntityRelationsViewProps {
  entityId: number
  entityTypeId: number
}

export function InverseEntityRelationsView({
  entityId,
  entityTypeId,
}: InverseEntityRelationsViewProps) {
  const { inverseFieldDefs, isLoading, isError, errorTitle } =
    useInverseRelationFieldDefs(entityTypeId)

  if (isLoading) {
    return <Text muted>Loading referenced-by relations…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load referenced-by relations</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
      </Stack>
    )
  }

  if (inverseFieldDefs.length === 0) {
    return null
  }

  return (
    <Stack gap="lg">
      <Text as="h2" variant="heading-sm">
        Referenced by
      </Text>
      {inverseFieldDefs.map((fieldDef) => (
        <InverseRelationPanel
          key={String(fieldDef.id)}
          fieldDef={fieldDef}
          targetEntityId={entityId}
        />
      ))}
    </Stack>
  )
}
