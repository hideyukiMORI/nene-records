import { useMemo } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useEntityTypeList } from '@/entities/entity-type'
import {
  PublicRecordDetailView,
  usePublicViewEntityRecordPage,
} from '@/features/public-view-entity-record'
import { findEntityTypeBySlug } from '@/shared/lib/find-entity-type-by-slug'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

function PublicRecordDetailContent({
  entityTypeSlug,
  entityTypeName,
  entityTypeId,
  entityId,
}: {
  entityTypeSlug: string
  entityTypeName: string
  entityTypeId: number
  entityId: number
}) {
  const { entity, fields, isLoading, isError, errorTitle, refetch } = usePublicViewEntityRecordPage(
    entityTypeId,
    entityId,
  )

  return (
    <Stack gap="md">
      <Stack gap="sm">
        <Link to={`/view/${entityTypeSlug}`}>
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
        fields={fields}
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

export function PublicRecordDetailPage() {
  const { entityTypeSlug = '', entityId: entityIdParam = '' } = useParams()
  const entityId = Number(entityIdParam)

  const entityTypeQuery = useEntityTypeList()
  const entityType = useMemo(
    () => findEntityTypeBySlug(entityTypeQuery.data?.items ?? [], entityTypeSlug),
    [entityTypeQuery.data?.items, entityTypeSlug],
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

  return (
    <PublicRecordDetailContent
      entityTypeSlug={entityTypeSlug}
      entityTypeName={entityType.name}
      entityTypeId={Number(entityType.id)}
      entityId={entityId}
    />
  )
}
