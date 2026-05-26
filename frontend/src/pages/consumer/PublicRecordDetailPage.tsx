import { useMemo } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useEntityTypeList } from '@/entities/entity-type'
import {
  PublicRecordDetailView,
  usePublicViewEntityRecordPage,
} from '@/features/public-view-entity-record'
import { findEntityTypeBySlug } from '@/shared/lib/find-entity-type-by-slug'
import { extractEntityKeyFromSplat } from '@/shared/lib/resolve-permalink'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'
import { useEntityIdBySlug } from './hooks/use-entity-id-by-slug'

function PublicRecordDetailContent({
  entityTypeSlug,
  entityTypeName,
  entityTypeId,
  entityId,
  entityTypeSlugById,
}: {
  entityTypeSlug: string
  entityTypeName: string
  entityTypeId: number
  entityId: number
  entityTypeSlugById: Record<number, string>
}) {
  const { entity, fieldRows, isLoading, isError, errorTitle, refetch } =
    usePublicViewEntityRecordPage(entityTypeId, entityId)

  return (
    <Stack gap="md">
      <Stack gap="sm">
        <Link to={`/${entityTypeSlug}`}>
          <Button variant="secondary" size="sm">
            Back to {entityTypeName}
          </Button>
        </Link>
        <Text as="h1" variant="heading-md">
          {entityTypeName}
        </Text>
      </Stack>
      <PublicRecordDetailView
        entity={entity}
        fieldRows={fieldRows}
        entityTypeSlugById={entityTypeSlugById}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        onRetry={() => {
          void refetch()
        }}
      />
    </Stack>
  )
}

/** Resolved splat → entityId, then renders content */
function PublicRecordDetailBySlug({
  entityTypeSlug,
  entityTypeName,
  entityTypeId,
  entitySlug,
  entityTypeSlugById,
}: {
  entityTypeSlug: string
  entityTypeName: string
  entityTypeId: number
  entitySlug: string
  entityTypeSlugById: Record<number, string>
}) {
  const { entityId, isLoading, isError } = useEntityIdBySlug(entityTypeId, entitySlug)

  if (isLoading) return <Text muted>Loading…</Text>
  if (isError || entityId === null) {
    return (
      <EmptyState title="Record not found" description={`No record with slug "${entitySlug}".`} />
    )
  }

  return (
    <PublicRecordDetailContent
      entityTypeSlug={entityTypeSlug}
      entityTypeName={entityTypeName}
      entityTypeId={entityTypeId}
      entityId={entityId}
      entityTypeSlugById={entityTypeSlugById}
    />
  )
}

export function PublicRecordDetailPage() {
  // React Router v6: splat param is '*'
  const { entityTypeSlug = '', '*': splat = '' } = useParams()

  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })
  const entityType = useMemo(
    () => findEntityTypeBySlug(entityTypeQuery.data?.items ?? [], entityTypeSlug),
    [entityTypeQuery.data?.items, entityTypeSlug],
  )
  const entityTypeSlugById = useMemo(
    (): Record<number, string> =>
      Object.fromEntries(
        (entityTypeQuery.data?.items ?? []).map((item) => [Number(item.id), item.slug]),
      ),
    [entityTypeQuery.data?.items],
  )

  if (entityTypeQuery.isLoading) {
    return <Text muted>Loading…</Text>
  }

  if (entityType === undefined) {
    return (
      <EmptyState
        title="Entity type not found"
        description={`No public content for "${entityTypeSlug}".`}
      />
    )
  }

  const entityTypeId = Number(entityType.id)
  const key = extractEntityKeyFromSplat(entityType.permalinkPattern, splat)

  if (key.kind === 'id') {
    return (
      <PublicRecordDetailContent
        entityTypeSlug={entityTypeSlug}
        entityTypeName={entityType.name}
        entityTypeId={entityTypeId}
        entityId={key.id}
        entityTypeSlugById={entityTypeSlugById}
      />
    )
  }

  return (
    <PublicRecordDetailBySlug
      entityTypeSlug={entityTypeSlug}
      entityTypeName={entityType.name}
      entityTypeId={entityTypeId}
      entitySlug={key.slug}
      entityTypeSlugById={entityTypeSlugById}
    />
  )
}
