import type { RelationFieldDef } from '@/entities/field-def'
import type { EntityRelationFilters } from '@/entities/entity'
import { Button, Stack, Text } from '@/shared/ui'
import { EntityRelationFilterField } from './EntityRelationFilterField'

export interface EntityRelationFilterPanelProps {
  relationFieldDefs: RelationFieldDef[]
  selectedFilters: EntityRelationFilters
  onSelectTarget: (fieldKey: string, targetEntityId: number | undefined) => void
  onClear: () => void
}

export function EntityRelationFilterPanel({
  relationFieldDefs,
  selectedFilters,
  onSelectTarget,
  onClear,
}: EntityRelationFilterPanelProps) {
  if (relationFieldDefs.length === 0) {
    return null
  }

  const isFilterActive = Object.keys(selectedFilters).length > 0

  return (
    <Stack gap="sm">
      <Stack direction="horizontal" gap="sm">
        <Text as="h2" variant="heading-sm">
          Filter by relation
        </Text>
        {isFilterActive ? (
          <Button variant="secondary" size="sm" onClick={onClear}>
            Clear
          </Button>
        ) : null}
      </Stack>
      <Stack gap="md">
        {relationFieldDefs.map((fieldDef) => (
          <EntityRelationFilterField
            key={fieldDef.fieldKey}
            fieldDef={fieldDef}
            selectedTargetId={selectedFilters[fieldDef.fieldKey]}
            onSelectTarget={(targetEntityId) => {
              onSelectTarget(fieldDef.fieldKey, targetEntityId)
            }}
          />
        ))}
      </Stack>
      {isFilterActive ? (
        <Text muted>Showing records matching all selected relation filters.</Text>
      ) : null}
    </Stack>
  )
}
