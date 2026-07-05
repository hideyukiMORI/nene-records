import type { RelationFieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { Button, Select, Stack, Text } from '@/shared/ui'
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
  const { t } = useTranslation()
  const { targetOptions, isLoading, isError, errorTitle, refetch } =
    useEntityRelationFilterField(fieldDef)

  const selectId = `relation-filter-${fieldDef.fieldKey}`

  if (isLoading) {
    return <Text muted>{t('admin.relations.filter.loading', { field: fieldDef.fieldKey })}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">
          {t('admin.relations.filter.error', { field: fieldDef.fieldKey })}
        </Text>
        <Text muted>{errorTitle ?? t('common.error.unknown')}</Text>
        <Button variant="secondary" onClick={() => void refetch()}>
          {t('common.actions.retry')}
        </Button>
      </Stack>
    )
  }

  return (
    <div className="flex flex-col gap-stack-xs">
      <label htmlFor={selectId} className="font-sans text-body font-medium text-text-primary">
        {fieldDef.fieldKey}
      </label>
      <Select
        id={selectId}
        disabled={targetOptions.length === 0}
        value={selectedTargetId === undefined ? '' : String(selectedTargetId)}
        onChange={(event) => {
          const value = event.target.value
          onSelectTarget(value === '' ? undefined : Number(value))
        }}
      >
        <option value="">
          {targetOptions.length === 0
            ? t('admin.relations.noTargetsAvailable')
            : t('admin.relations.filter.anyTarget')}
        </option>
        {targetOptions.map((option) => (
          <option key={String(option.id)} value={String(option.id)}>
            {option.label}
          </option>
        ))}
      </Select>
    </div>
  )
}
