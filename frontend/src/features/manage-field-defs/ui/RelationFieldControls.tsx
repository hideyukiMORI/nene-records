import { type Control, Controller, useWatch } from 'react-hook-form'
import { useEntityTypeList } from '@/entities/entity-type'
import { RELATION_CARDINALITIES } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import { Select } from '@/shared/ui'
import type { CreateFieldDefFormValues } from '../hooks/use-create-field-def-form'

const CARDINALITY_LABEL_KEYS: Record<(typeof RELATION_CARDINALITIES)[number], MessageKey> = {
  one: 'admin.fieldDefs.relation.cardinalityOne',
  many: 'admin.fieldDefs.relation.cardinalityMany',
}

export interface RelationFieldControlsProps {
  control: Control<CreateFieldDefFormValues>
  isSubmitting: boolean
  /** Distinguishes create vs edit form field ids. */
  idPrefix: string
  targetError?: string | undefined
}

/**
 * Target content type + cardinality selectors, shown only when the field's
 * data type is `relation`. The backend validates these (target_entity_type_id /
 * cardinality); without this UI a relation field cannot be configured.
 */
export function RelationFieldControls({
  control,
  isSubmitting,
  idPrefix,
  targetError,
}: RelationFieldControlsProps) {
  const { t } = useTranslation()
  const dataType = useWatch({ control, name: 'dataType' })
  const entityTypesQuery = useEntityTypeList({ limit: 100, offset: 0 })

  if (dataType !== 'relation') {
    return null
  }

  const entityTypes = entityTypesQuery.data?.items ?? []

  return (
    <>
      <Controller
        name="targetEntityTypeId"
        control={control}
        render={({ field }) => (
          <div className="flex flex-col gap-stack-xs">
            <label
              htmlFor={`${idPrefix}-relation-target`}
              className="font-sans text-body font-medium text-text-primary"
            >
              {t('admin.fieldDefs.relation.targetLabel')}
            </label>
            <Select
              id={`${idPrefix}-relation-target`}
              disabled={isSubmitting}
              value={field.value === undefined ? '' : String(field.value)}
              onChange={(e) => {
                field.onChange(e.target.value === '' ? undefined : Number(e.target.value))
              }}
              onBlur={field.onBlur}
            >
              <option value="">{t('admin.fieldDefs.relation.targetPlaceholder')}</option>
              {entityTypes.map((entityType) => (
                <option key={String(entityType.id)} value={String(entityType.id)}>
                  {entityType.name}
                </option>
              ))}
            </Select>
            {targetError !== undefined ? (
              <span className="font-sans text-caption text-danger">{targetError}</span>
            ) : null}
          </div>
        )}
      />
      <Controller
        name="cardinality"
        control={control}
        render={({ field }) => (
          <Select
            id={`${idPrefix}-relation-cardinality`}
            label={t('admin.fieldDefs.relation.cardinalityLabel')}
            disabled={isSubmitting}
            value={field.value ?? 'one'}
            onChange={field.onChange}
            onBlur={field.onBlur}
          >
            {RELATION_CARDINALITIES.map((cardinality) => (
              <option key={cardinality} value={cardinality}>
                {t(CARDINALITY_LABEL_KEYS[cardinality])}
              </option>
            ))}
          </Select>
        )}
      />
    </>
  )
}
