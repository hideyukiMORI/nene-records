import { Link } from 'react-router-dom'
import type { RelationFieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'
import { usePublicRelationFieldDisplay } from '../hooks/use-public-relation-field-display'

export interface PublicRelationFieldDisplayProps {
  entityId: number
  fieldDef: RelationFieldDef
  entityTypeSlugById: Record<number, string>
  entityTypePatternById: Record<number, string | null | undefined>
}

export function PublicRelationFieldDisplay({
  entityId,
  fieldDef,
  entityTypeSlugById,
  entityTypePatternById,
}: PublicRelationFieldDisplayProps) {
  const { t } = useTranslation()
  const { targets, isLoading, isError, errorTitle, refetch } = usePublicRelationFieldDisplay(
    entityId,
    fieldDef,
    entityTypeSlugById,
    entityTypePatternById,
  )

  if (isLoading) {
    // Skeleton, not text (#894/#905): the dt (field key) is real content and stays;
    // only the resolving value gets a line-shaped placeholder.
    return (
      <div className="flex flex-col gap-stack-xs">
        <Text as="dt" variant="heading-sm">
          {fieldDef.fieldKey}
        </Text>
        <dd className="loading-view m-0" aria-busy="true">
          <span className="skeleton sk-line" />
        </dd>
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
            {errorTitle ?? t('public.relation.loadError')}
          </Text>
          <Button variant="secondary" size="sm" onClick={() => void refetch()}>
            {t('common.actions.retry')}
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
              viewTransition
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
